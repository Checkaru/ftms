<?php

namespace App\Policies;

use App\Models\TrainingPeriod;
use App\Models\User;

/**
 * Training periods are managed exclusively by the coordinator.
 */
class TrainingPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function view(User $user, TrainingPeriod $period): bool
    {
        return $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, TrainingPeriod $period): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, TrainingPeriod $period): bool
    {
        return $user->isCoordinator();
    }
}
