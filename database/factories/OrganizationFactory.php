<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'sector' => fake()->randomElement([
                'تقنية المعلومات', 'الاتصالات', 'التعليم', 'الصحة', 'المصارف', 'المقاولات',
            ]),
            'address' => fake()->city(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('05########'),
            'is_active' => true,
        ];
    }
}
