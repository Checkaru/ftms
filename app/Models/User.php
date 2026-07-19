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

    // Messaging ---------------------------------------------------------------

    /**
     * Conversations this user may see: their DMs, plus the discussion threads
     * of every placement they are a stakeholder in (all threads for the
     * coordinator). Returns the two id sets merged.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function accessibleConversationIds(): \Illuminate\Support\Collection
    {
        $dmIds = Conversation::whereNull('placement_id')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $this->id))
            ->pluck('id');

        $threadIds = Conversation::whereNotNull('placement_id')
            ->whereHas('placement', function ($q) {
                match ($this->role) {
                    UserRole::Coordinator => null,
                    UserRole::Student => $q->where('student_id', $this->id),
                    UserRole::FieldSupervisor => $q->where('organization_id', $this->organization_id),
                    UserRole::AcademicSupervisor => $q->where('academic_supervisor_id', $this->id),
                };
            })
            ->pluck('id');

        return $dmIds->merge($threadIds)->unique()->values();
    }

    /** Conversations with unseen messages — the nav badge number. */
    public function unreadConversationsCount(): int
    {
        $ids = $this->accessibleConversationIds();

        if ($ids->isEmpty()) {
            return 0;
        }

        return Message::whereIn('conversation_id', $ids)
            ->where(fn ($q) => $q->where('sender_id', '!=', $this->id)->orWhereNull('sender_id'))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('conversation_participants as cp')
                    ->whereColumn('cp.conversation_id', 'messages.conversation_id')
                    ->where('cp.user_id', $this->id)
                    ->whereColumn('cp.last_read_at', '>=', 'messages.created_at');
            })
            ->distinct()
            ->count('conversation_id');
    }

    /**
     * Who this user may start a DM with. The graph follows the org/assignment
     * structure: nobody can cold-message an unrelated user, and the
     * coordinator can reach everyone.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function contactableUsers(): \Illuminate\Database\Eloquent\Collection
    {
        $base = self::query()->where('id', '!=', $this->id)->where('is_active', true);

        return match ($this->role) {
            UserRole::Coordinator => $base->orderBy('name')->get(),

            UserRole::Student => $base->where(function ($q) {
                $placements = Placement::where('student_id', $this->id);
                $q->where('role', UserRole::Coordinator)
                    ->orWhere(fn ($q) => $q->where('role', UserRole::FieldSupervisor)
                        ->whereIn('organization_id', (clone $placements)->pluck('organization_id')))
                    ->orWhereIn('id', (clone $placements)->whereNotNull('academic_supervisor_id')
                        ->pluck('academic_supervisor_id'));
            })->orderBy('name')->get(),

            UserRole::FieldSupervisor => $base->where(function ($q) {
                $placements = Placement::where('organization_id', $this->organization_id);
                $q->where('role', UserRole::Coordinator)
                    ->orWhereIn('id', (clone $placements)->pluck('student_id'))
                    ->orWhereIn('id', (clone $placements)->whereNotNull('academic_supervisor_id')
                        ->pluck('academic_supervisor_id'));
            })->orderBy('name')->get(),

            UserRole::AcademicSupervisor => $base->where(function ($q) {
                $placements = Placement::where('academic_supervisor_id', $this->id);
                $q->where('role', UserRole::Coordinator)
                    ->orWhereIn('id', (clone $placements)->pluck('student_id'))
                    ->orWhere(fn ($q) => $q->where('role', UserRole::FieldSupervisor)
                        ->whereIn('organization_id', (clone $placements)->pluck('organization_id')));
            })->orderBy('name')->get(),
        };
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
