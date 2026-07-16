<?php

namespace Database\Factories;

use App\Enums\PlacementStatus;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'organization_id' => Organization::factory(),
            'period_id' => TrainingPeriod::factory(),
            'field_supervisor_id' => null,
            'academic_supervisor_id' => null,
            'status' => PlacementStatus::Active,
        ];
    }
}
