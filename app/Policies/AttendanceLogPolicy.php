<?php

namespace App\Policies;

use App\Models\AttendanceLog;
use App\Models\User;

class AttendanceLogPolicy
{
    /**
     * Who may see a single log: the coordinator (reports), the student who owns
     * the placement, the field supervisor at the host org, and the assigned
     * academic supervisor.
     */
    public function view(User $user, AttendanceLog $log): bool
    {
        $placement = $log->placement;

        return $user->isCoordinator()
            || ($user->isStudent() && $placement->student_id === $user->id)
            || ($user->isFieldSupervisor() && $placement->organization_id === $user->organization_id)
            || ($user->isAcademicSupervisor() && $placement->academic_supervisor_id === $user->id);
    }

    /** A student may edit only their own log, and only while it is not approved. */
    public function update(User $user, AttendanceLog $log): bool
    {
        return $user->isStudent()
            && $log->placement->student_id === $user->id
            && $log->isEditableByStudent();
    }

    /** Same rule as update: own log, not yet approved. */
    public function delete(User $user, AttendanceLog $log): bool
    {
        return $this->update($user, $log);
    }

    /**
     * The bug this system will actually have is a field supervisor approving
     * hours for a student who isn't theirs. This is the row-level gate that
     * stops it: field supervisor AND the log's placement is at their org.
     */
    public function review(User $user, AttendanceLog $log): bool
    {
        return $user->isFieldSupervisor()
            && $log->placement->organization_id === $user->organization_id;
    }
}
