<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Nothing is mass-assignable: conversations are only created through the two
// factory methods below, never from request data.
#[Fillable([])]
class Conversation extends Model
{
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isPlacementThread(): bool
    {
        return $this->placement_id !== null;
    }

    /** The other side of a DM, from $user's perspective. */
    public function otherParticipant(User $user): ?User
    {
        return $this->participants->first(fn (User $p) => $p->id !== $user->id);
    }

    /** The single discussion thread for a placement (unique at the DB level). */
    public static function forPlacement(Placement $placement): self
    {
        $existing = self::where('placement_id', $placement->id)->first();

        if ($existing) {
            return $existing;
        }

        $conversation = new self();
        $conversation->placement_id = $placement->id;
        $conversation->save();

        return $conversation;
    }

    /** The single DM conversation between two users, creating it if needed. */
    public static function dmBetween(User $a, User $b): self
    {
        $existing = self::whereNull('placement_id')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $b->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = new self();
        $conversation->save();
        $conversation->participants()->attach([$a->id, $b->id]);

        return $conversation;
    }

    /** Record that $user has read everything up to now. */
    public function markReadBy(User $user): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => ['last_read_at' => now()],
        ]);
    }

    /** Whether $user has messages here they haven't seen (their own don't count). */
    public function hasUnreadFor(User $user): bool
    {
        $latest = $this->latestMessage;

        if ($latest === null || $latest->sender_id === $user->id) {
            return false;
        }

        $pivot = $this->participants->firstWhere('id', $user->id)?->pivot;

        return $pivot?->last_read_at === null
            || $latest->created_at->greaterThan($pivot->last_read_at);
    }
}
