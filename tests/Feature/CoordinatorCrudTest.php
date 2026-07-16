<?php

namespace Tests\Feature;

use App\Enums\PlacementStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoordinatorCrudTest extends TestCase
{
    use RefreshDatabase;

    private function coordinator(): User
    {
        return User::factory()->coordinator()->create();
    }

    public function test_coordinator_can_create_an_organization(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('coordinator.organizations.store'), [
                'name' => 'شركة اختبار',
                'sector' => 'تقنية المعلومات',
                'is_active' => '1',
            ])
            ->assertRedirect(route('coordinator.organizations.index'));

        $this->assertDatabaseHas('organizations', ['name' => 'شركة اختبار']);
    }

    public function test_non_coordinator_cannot_create_an_organization(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->post(route('coordinator.organizations.store'), ['name' => 'x'])
            ->assertForbidden();

        $this->assertDatabaseMissing('organizations', ['name' => 'x']);
    }

    public function test_opening_a_period_closes_any_other_open_period(): void
    {
        $old = TrainingPeriod::factory()->open()->create(['name' => 'قديمة']);

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.periods.store'), [
                'name' => 'جديدة',
                'starts_on' => '2026-07-01',
                'ends_on' => '2026-09-30',
                'required_hours' => 180,
                'is_open' => '1',
            ])
            ->assertRedirect(route('coordinator.periods.index'));

        $this->assertFalse($old->fresh()->is_open);
        $this->assertSame(1, TrainingPeriod::where('is_open', true)->count());
    }

    public function test_coordinator_creates_a_user_with_a_hashed_password_and_role(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('coordinator.users.store'), [
                'name' => 'طالب جديد',
                'email' => 'new.student@taqat.ps',
                'role' => UserRole::Student->value,
                'student_number' => '20260001',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('coordinator.users.index'));

        $user = User::where('email', 'new.student@taqat.ps')->firstOrFail();
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_duplicate_student_period_placement_is_rejected(): void
    {
        $period = TrainingPeriod::factory()->open()->create();
        $org = Organization::factory()->create();
        $student = User::factory()->student()->create();

        Placement::factory()->create([
            'student_id' => $student->id,
            'organization_id' => $org->id,
            'period_id' => $period->id,
        ]);

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.placements.store'), [
                'student_id' => $student->id,
                'organization_id' => $org->id,
                'period_id' => $period->id,
                'status' => PlacementStatus::Active->value,
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(1, Placement::where('student_id', $student->id)->count());
    }

    public function test_field_supervisor_from_another_org_is_rejected_on_placement(): void
    {
        $period = TrainingPeriod::factory()->open()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $student = User::factory()->student()->create();
        $fieldB = User::factory()->fieldSupervisor($orgB)->create();

        $this->actingAs($this->coordinator())
            ->post(route('coordinator.placements.store'), [
                'student_id' => $student->id,
                'organization_id' => $orgA->id,
                'period_id' => $period->id,
                'field_supervisor_id' => $fieldB->id,
                'status' => PlacementStatus::Active->value,
            ])
            ->assertSessionHasErrors('field_supervisor_id');

        $this->assertSame(0, Placement::count());
    }
}
