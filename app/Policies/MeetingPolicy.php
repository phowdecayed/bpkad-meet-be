<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view meetings');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->organizer_id
            || $user->can('view meetings')
            || $meeting->participants->contains($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create meetings');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->organizer_id || $user->can('edit meetings');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->organizer_id || $user->can('delete meetings');
    }

    /**
     * Determine whether the user can manage participants for the model.
     */
    public function manageParticipants(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->organizer_id || $user->can('edit meetings');
    }

    /**
     * Determine whether the user can view the host key for the model.
     */
    public function viewHostKey(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->organizer_id || $user->can('edit meetings');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Meeting $meeting): bool
    {
        return $user->can('manage meetings');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Meeting $meeting): bool
    {
        return $user->can('manage meetings');
    }
}
