<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: User, 3: Placement}  [student, fieldSup, academicSup, placement]
     */
    private function placementWithStakeholders(): array
    {
        $org = Organization::factory()->create();
        $student = User::factory()->student()->create();
        $field = User::factory()->fieldSupervisor($org)->create();
        $academic = User::factory()->academicSupervisor()->create();

        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'organization_id' => $org->id,
            'period_id' => TrainingPeriod::factory()->open()->create()->id,
            'field_supervisor_id' => $field->id,
            'academic_supervisor_id' => $academic->id,
        ]);

        return [$student, $field, $academic, $placement];
    }

    public function test_stakeholders_share_one_placement_thread_and_can_talk(): void
    {
        [$student, $field, , $placement] = $this->placementWithStakeholders();

        // Student opens the thread (creates it) and posts.
        $this->actingAs($student)->get(route('messages.placement', $placement))->assertRedirect();
        $conversation = Conversation::where('placement_id', $placement->id)->firstOrFail();

        $this->actingAs($student)
            ->post(route('messages.store', $conversation), ['body' => 'سؤال عن السجل'])
            ->assertRedirect(route('messages.show', $conversation));

        // Field supervisor opens the SAME thread and replies.
        $this->actingAs($field)->get(route('messages.placement', $placement))
            ->assertRedirect(route('messages.show', $conversation));

        $this->actingAs($field)
            ->post(route('messages.store', $conversation), ['body' => 'تم التوضيح'])
            ->assertRedirect();

        $this->assertSame(1, Conversation::where('placement_id', $placement->id)->count());
        $this->assertSame(2, $conversation->messages()->count());

        // Student sees the reply on the thread page.
        $this->actingAs($student)->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('تم التوضيح');
    }

    public function test_a_foreign_field_supervisor_cannot_open_or_post_in_the_thread(): void
    {
        [, , , $placement] = $this->placementWithStakeholders();
        $foreign = User::factory()->fieldSupervisor(Organization::factory()->create())->create();

        $this->actingAs($foreign)->get(route('messages.placement', $placement))->assertForbidden();

        $conversation = Conversation::forPlacement($placement);
        $this->actingAs($foreign)->get(route('messages.show', $conversation))->assertForbidden();
        $this->actingAs($foreign)
            ->post(route('messages.store', $conversation), ['body' => 'تسلل'])
            ->assertForbidden();

        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_coordinator_can_read_and_post_in_any_thread(): void
    {
        [, , , $placement] = $this->placementWithStakeholders();
        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)->get(route('messages.placement', $placement))->assertRedirect();

        $conversation = Conversation::forPlacement($placement);
        $this->actingAs($coordinator)
            ->post(route('messages.store', $conversation), ['body' => 'ملاحظة من المنسق'])
            ->assertRedirect();

        $this->assertSame(1, $conversation->messages()->count());
    }

    public function test_student_can_dm_their_supervisor_and_the_pair_reuses_one_conversation(): void
    {
        [$student, $field] = $this->placementWithStakeholders();

        $this->actingAs($student)
            ->post(route('messages.storeDm'), ['recipient_id' => $field->id, 'body' => 'مرحباً'])
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('messages.storeDm'), ['recipient_id' => $field->id, 'body' => 'رسالة ثانية'])
            ->assertRedirect();

        $this->assertSame(1, Conversation::whereNull('placement_id')->count());
        $this->assertSame(2, Conversation::whereNull('placement_id')->first()->messages()->count());
    }

    public function test_student_cannot_dm_an_unrelated_user(): void
    {
        [$student] = $this->placementWithStakeholders();
        $stranger = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('messages.storeDm'), ['recipient_id' => $stranger->id, 'body' => 'مرحباً'])
            ->assertSessionHasErrors('recipient_id');

        $this->assertSame(0, Conversation::count());
    }

    public function test_contact_list_is_scoped_by_role(): void
    {
        [$student, $field, $academic] = $this->placementWithStakeholders();
        $coordinator = User::factory()->coordinator()->create();
        $stranger = User::factory()->student()->create();

        $contacts = $student->contactableUsers()->pluck('id');

        $this->assertTrue($contacts->contains($field->id));
        $this->assertTrue($contacts->contains($academic->id));
        $this->assertTrue($contacts->contains($coordinator->id));
        $this->assertFalse($contacts->contains($stranger->id));
    }

    public function test_unread_count_rises_on_new_message_and_clears_on_view(): void
    {
        [$student, $field] = $this->placementWithStakeholders();

        $this->actingAs($student)
            ->post(route('messages.storeDm'), ['recipient_id' => $field->id, 'body' => 'سؤال']);

        $conversation = Conversation::whereNull('placement_id')->first();

        $this->assertSame(1, $field->unreadConversationsCount());
        $this->assertSame(0, $student->unreadConversationsCount()); // own message doesn't count

        $this->actingAs($field)->get(route('messages.show', $conversation))->assertOk();

        $this->assertSame(0, $field->unreadConversationsCount());
    }

    public function test_inbox_lists_threads_and_dms_and_hides_foreign_ones(): void
    {
        [$student, $field, , $placement] = $this->placementWithStakeholders();

        // A thread with a message + a DM.
        $thread = Conversation::forPlacement($placement);
        $this->actingAs($student)->post(route('messages.store', $thread), ['body' => 'في المناقشة']);
        $this->actingAs($student)->post(route('messages.storeDm'), ['recipient_id' => $field->id, 'body' => 'خاص']);

        // Someone unrelated sees neither.
        $outsider = User::factory()->student()->create(['name' => 'طالب خارجي']);

        $this->actingAs($field)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('في المناقشة')
            ->assertSee('خاص');

        $this->actingAs($outsider)->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('في المناقشة')
            ->assertDontSee('خاص');
    }
}
