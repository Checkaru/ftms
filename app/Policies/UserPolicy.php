<?php

namespace App\Policies;

use App\Models\User;

/**
 * User accounts are created and managed exclusively by the coordinator.
 * (Editing one's own profile is handled by Breeze's ProfileController, not here.)
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, User $model): bool
    {
        // A coordinator manages other users but cannot delete their own account here.
        return $user->isCoordinator() && $user->id !== $model->id;
    }
}
