<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Every coordinator screen renders without error for a coordinator. */
    public function test_all_coordinator_pages_render(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $org = Organization::factory()->create();
        $period = TrainingPeriod::factory()->open()->create();
        $placement = Placement::factory()->create([
            'organization_id' => $org->id,
            'period_id' => $period->id,
        ]);

        $this->actingAs($coordinator);

        $pages = [
            route('coordinator.dashboard'),
            route('coordinator.organizations.index'),
            route('coordinator.organizations.create'),
            route('coordinator.organizations.edit', $org),
            route('coordinator.periods.index'),
            route('coordinator.periods.create'),
            route('coordinator.periods.edit', $period),
            route('coordinator.placements.index'),
            route('coordinator.placements.create'),
            route('coordinator.placements.edit', $placement),
            route('coordinator.users.index'),
            route('coordinator.users.create'),
            route('coordinator.users.edit', $coordinator),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
    }
}
