<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Placement threads are open to the placement's stakeholders and the
     * coordinator; DMs only to their two participants.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->isPlacementThread()) {
            $placement = $conversation->placement;

            return $user->isCoordinator()
                || $placement->student_id === $user->id
                || ($user->isFieldSupervisor() && $placement->organization_id === $user->organization_id)
                || $placement->academic_supervisor_id === $user->id;
        }

        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /** Anyone who can read a conversation can write in it. */
    public function post(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
