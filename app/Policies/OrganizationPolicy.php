<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

/**
 * Organisations are managed exclusively by the coordinator.
 */
class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isCoordinator();
    }
}
