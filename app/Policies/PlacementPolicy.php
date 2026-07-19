<?php

namespace App\Policies;

use App\Enums\PlacementStatus;
use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    /**
     * Who may browse a placement list. Each such controller still scopes the
     * query to the actor (coordinator sees all; supervisors see only theirs).
     */
    public function viewAny(User $user): bool
    {
        return $user->isCoordinator()
            || $user->isFieldSupervisor()
            || $user->isAcademicSupervisor();
    }

    /**
     * Who may see a placement: coordinator, its student, the field supervisor at
     * its host org, and its assigned academic supervisor.
     */
    public function view(User $user, Placement $placement): bool
    {
        return $user->isCoordinator()
            || ($user->isStudent() && $placement->student_id === $user->id)
            || ($user->isFieldSupervisor() && $placement->organization_id === $user->organization_id)
            || ($user->isAcademicSupervisor() && $placement->academic_supervisor_id === $user->id);
    }

    // Placements are created and managed only by the coordinator.

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $user->isCoordinator();
    }

    /**
     * A student may log attendance against their own placement, while it is
     * active and its period is still open.
     */
    public function logAttendance(User $user, Placement $placement): bool
    {
        return $user->isStudent()
            && $placement->student_id === $user->id
            && $placement->status === PlacementStatus::Active
            && $placement->period->is_open;
    }

    /**
     * Only the field supervisor at the placement's host org may submit the
     * field evaluation. (Whether one already exists is enforced by the DB
     * unique(placement_id, kind); this only gates who may write it.)
     */
    public function submitFieldEvaluation(User $user, Placement $placement): bool
    {
        return $user->isFieldSupervisor()
            && $placement->organization_id === $user->organization_id;
    }

    /** Only the assigned academic supervisor may submit the academic evaluation. */
    public function submitAcademicEvaluation(User $user, Placement $placement): bool
    {
        return $user->isAcademicSupervisor()
            && $placement->academic_supervisor_id === $user->id;
    }

    /**
     * Who may open the placement's discussion thread: its stakeholders and
     * the coordinator — the same circle that can see the placement.
     */
    public function discuss(User $user, Placement $placement): bool
    {
        return $user->isCoordinator()
            || $placement->student_id === $user->id
            || ($user->isFieldSupervisor() && $placement->organization_id === $user->organization_id)
            || $placement->academic_supervisor_id === $user->id;
    }
}
