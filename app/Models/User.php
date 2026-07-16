<?php

namespace App\Models;

use App\Enums\PlacementStatus;
use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// `role` is deliberately absent: it is never mass-assignable. It is set only
// when the coordinator creates or edits a user. See PROJECT.md → Security.
#[Fillable(['name', 'email', 'password', 'phone', 'student_number', 'organization_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    // Relationships -----------------------------------------------------------

    /** The host organisation a field supervisor belongs to (null for others). */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Placements where this user is the trainee. */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'student_id');
    }

    /** Placements this user supervises in the field. */
    public function fieldPlacements(): HasMany
    {
        return $this->hasMany(Placement::class, 'field_supervisor_id');
    }

    /** Placements this user supervises academically. */
    public function academicPlacements(): HasMany
    {
        return $this->hasMany(Placement::class, 'academic_supervisor_id');
    }

    /**
     * The student's current placement: an active placement in the open period.
     * Everything a student logs hangs off this. Null if they have none.
     */
    public function activePlacement(): ?Placement
    {
        return $this->placements()
            ->where('status', PlacementStatus::Active)
            ->whereHas('period', fn ($q) => $q->where('is_open', true))
            ->with(['period', 'organization'])
            ->first();
    }

    // Role helpers ------------------------------------------------------------

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isFieldSupervisor(): bool
    {
        return $this->role === UserRole::FieldSupervisor;
    }

    public function isAcademicSupervisor(): bool
    {
        return $this->role === UserRole::AcademicSupervisor;
    }

    public function isCoordinator(): bool
    {
        return $this->role === UserRole::Coordinator;
    }
}
