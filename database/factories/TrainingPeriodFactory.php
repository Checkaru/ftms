<?php

namespace Database\Factories;

use App\Models\TrainingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingPeriod>
 */
class TrainingPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = now()->subWeeks(4)->startOfDay();

        return [
            'name' => 'صيف 2026',
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->addMonths(3),
            'required_hours' => 180,
            'is_open' => false,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => true,
        ]);
    }
}
