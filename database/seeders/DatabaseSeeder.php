<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;

// NOTE: deliberately NOT using WithoutModelEvents — the AttendanceLog `saving`
// hook must run so `minutes` is computed from check-in/out.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // One open training period. Only one is ever open at a time.
        $period = TrainingPeriod::factory()->open()->create([
            'name' => 'صيف 2026',
            'required_hours' => 180,
        ]);

        // Two host organisations.
        $orgA = Organization::factory()->create(['name' => 'شركة تقنيات الأفق', 'sector' => 'تقنية المعلومات']);
        $orgB = Organization::factory()->create(['name' => 'مؤسسة نماء للبرمجيات', 'sector' => 'تقنية المعلومات']);

        // Coordinator.
        User::factory()->coordinator()->create([
            'name' => 'منسق التدريب',
            'email' => 'coordinator@taqat.ps',
        ]);

        // One field supervisor per organisation (approves only their org).
        $fieldA = User::factory()->fieldSupervisor($orgA)->create([
            'name' => 'المشرف الميداني - الأفق',
            'email' => 'field.a@taqat.ps',
        ]);
        $fieldB = User::factory()->fieldSupervisor($orgB)->create([
            'name' => 'المشرف الميداني - نماء',
            'email' => 'field.b@taqat.ps',
        ]);

        // Academic supervisor (university side), shared across students here.
        $academic = User::factory()->academicSupervisor()->create([
            'name' => 'المشرف الأكاديمي',
            'email' => 'academic@taqat.ps',
        ]);

        // Three students.
        $s1 = User::factory()->student()->create(['name' => 'أحمد اليعقوبي', 'email' => 'student1@taqat.ps']);
        $s2 = User::factory()->student()->create(['name' => 'سارة النجار', 'email' => 'student2@taqat.ps']);
        $s3 = User::factory()->student()->create(['name' => 'محمود العطار', 'email' => 'student3@taqat.ps']);

        // Placements: student → org (with the matching field supervisor).
        $p1 = Placement::factory()->create([
            'student_id' => $s1->id, 'organization_id' => $orgA->id, 'period_id' => $period->id,
            'field_supervisor_id' => $fieldA->id, 'academic_supervisor_id' => $academic->id,
        ]);
        $p2 = Placement::factory()->create([
            'student_id' => $s2->id, 'organization_id' => $orgB->id, 'period_id' => $period->id,
            'field_supervisor_id' => $fieldB->id, 'academic_supervisor_id' => $academic->id,
        ]);
        $p3 = Placement::factory()->create([
            'student_id' => $s3->id, 'organization_id' => $orgA->id, 'period_id' => $period->id,
            'field_supervisor_id' => $fieldA->id, 'academic_supervisor_id' => $academic->id,
        ]);

        // ~20 attendance logs in mixed states. The reviewer of an approved/rejected
        // log is always the placement's own field supervisor.
        $this->seedLogs($p1, $fieldA, ['approved', 'approved', 'approved', 'approved', 'approved', 'pending', 'pending', 'rejected']);
        $this->seedLogs($p2, $fieldB, ['approved', 'approved', 'approved', 'approved', 'pending', 'pending', 'rejected']);
        $this->seedLogs($p3, $fieldA, ['approved', 'approved', 'approved', 'pending', 'pending', 'rejected']);
    }

    /**
     * Create attendance logs on consecutive days for one placement.
     *
     * @param  array<int, string>  $plan  status per day
     */
    private function seedLogs(Placement $placement, User $reviewer, array $plan): void
    {
        $date = $placement->period->starts_on->copy();

        foreach ($plan as $status) {
            $date->addDay();

            $factory = AttendanceLog::factory()->for($placement);

            $factory = match ($status) {
                'approved' => $factory->approved($reviewer),
                'rejected' => $factory->rejected($reviewer),
                default => $factory->pending(),
            };

            $factory->create(['work_date' => $date->format('Y-m-d')]);
        }
    }
}
