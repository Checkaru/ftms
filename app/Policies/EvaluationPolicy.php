<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    /**
     * Who may view a submitted evaluation: the coordinator, the evaluator who
     * wrote it, the field/academic supervisors tied to the placement, and the
     * student — but only once it has been released (submitted). A student never
     * sees an unsubmitted draft.
     */
    public function view(User $user, Evaluation $evaluation): bool
    {
        $placement = $evaluation->placement;

        if ($user->isCoordinator()) {
            return true;
        }

        if ($evaluation->evaluator_id === $user->id) {
            return true;
        }

        if ($user->isFieldSupervisor() && $placement->organization_id === $user->organization_id) {
            return true;
        }

        if ($user->isAcademicSupervisor() && $placement->academic_supervisor_id === $user->id) {
            return true;
        }

        // The student sees their own evaluation only after it is released.
        return $user->isStudent()
            && $placement->student_id === $user->id
            && $evaluation->submitted_at !== null;
    }
}
