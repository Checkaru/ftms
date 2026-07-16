<?php

namespace Database\Factories;

use App\Enums\LogStatus;
use App\Models\AttendanceLog;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkInHour = fake()->numberBetween(8, 9);
        $checkOutHour = $checkInHour + fake()->numberBetween(5, 7);

        return [
            'placement_id' => Placement::factory(),
            'work_date' => fake()->dateTimeBetween('-3 weeks', 'now')->format('Y-m-d'),
            'check_in' => sprintf('%02d:00', $checkInHour),
            'check_out' => sprintf('%02d:00', $checkOutHour),
            // minutes is derived in the model's saving hook — no need to set it here.
            'tasks' => fake()->randomElement([
                'تحليل متطلبات النظام مع الفريق',
                'تطوير واجهات المستخدم',
                'اختبار الوحدات وإصلاح الأخطاء',
                'مراجعة الكود وتوثيقه',
                'اجتماع متابعة مع المشرف',
            ]),
            'status' => LogStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LogStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ]);
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LogStatus::Approved,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'reject_reason' => null,
        ]);
    }

    public function rejected(?User $reviewer = null, string $reason = 'التوقيت غير مطابق لسجل المؤسسة'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LogStatus::Rejected,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'reject_reason' => $reason,
        ]);
    }
}
