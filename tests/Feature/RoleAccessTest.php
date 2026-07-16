<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/coordinator')->assertRedirect('/login');
    }

    public function test_student_cannot_enter_the_coordinator_section(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/coordinator')->assertForbidden();
    }

    public function test_coordinator_can_enter_the_coordinator_section(): void
    {
        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)->get('/coordinator')->assertOk();
    }

    public function test_dashboard_dispatches_each_role_to_its_home(): void
    {
        $org = Organization::factory()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->get('/dashboard')->assertRedirect('/coordinator');

        $this->actingAs(User::factory()->student()->create())
            ->get('/dashboard')->assertRedirect('/student');

        $this->actingAs(User::factory()->fieldSupervisor($org)->create())
            ->get('/dashboard')->assertRedirect('/field');

        $this->actingAs(User::factory()->academicSupervisor()->create())
            ->get('/dashboard')->assertRedirect('/academic');
    }

    public function test_inactive_user_is_forbidden_from_their_section(): void
    {
        $coordinator = User::factory()->coordinator()->create(['is_active' => false]);

        $this->actingAs($coordinator)->get('/coordinator')->assertForbidden();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
