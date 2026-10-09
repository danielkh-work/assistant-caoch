<?php

namespace App\Services;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Single gate every Global Plays endpoint calls to decide whether a user may
 * modify, delete, or restore a given play. Centralizing the rules here (rather
 * than repeating `if` checks in each controller action) means the business
 * rules below hold everywhere at once, including against crafted/direct API
 * calls that try to bypass UI-level restrictions.
 */
class PlayAuthorizationService
{
    /**
     * Whether the user can act in the given league at all (head coach owner,
     * shared via league_access, or an AC/performance coach whose head coach
     * owns it). Delegates entirely to League::isAccessibleBy - the single
     * already-correct "can this user act in this league" check - rather than
     * re-deriving that logic here.
     */
    public function userCanActInLeague(User $user, int $leagueId): bool
    {
        $league = League::find($leagueId);

        return $league && $league->isAccessibleBy($user);
    }

    /**
     * Create/Edit a non-global play, or (with $leagueId) customize a Global Play.
     * HC can touch anything in a league they can act in; AC can touch only plays
     * already scoped to one of their head coach's leagues, and ONLY if the play
     * isn't global. For a Global Play, $leagueId is which league's playbook is
     * doing the customizing - required, and checked the same way canRestore()
     * checks it, so a head coach can't target a league they have no relation to.
     */
    public function canModify(User $user, Play $play, ?int $leagueId = null): bool
    {
        if ($play->is_global) {
            if ($user->role !== 'head_coach') {
                return false;
            }

            return $leagueId !== null && $this->userCanActInLeague($user, $leagueId);
        }

        if ($play->league_id === null) {
            Log::warning('PlayAuthorizationService::canModify - non-global play has null league_id', [
                'play_id' => $play->id,
            ]);

            return false;
        }

        return $this->userCanActInLeague($user, (int) $play->league_id);
    }

    /**
     * Delete (or, for a Global Play, hide-from-playbook for one specific league).
     * Head coach only, ever. For a Global Play, $leagueId is which league's
     * playbook is hiding it from - required and checked, exactly like canRestore(),
     * so a head coach can't hide a Global Play out of a league they have no
     * relation to (that would otherwise let HC-A sabotage HC-B's playbook).
     *
     * Intentionally NOT delegated to a Spatie permission check alone: delete
     * is restricted to head_coach specifically, regardless of any play.delete
     * permission an assistant/performance coach role might otherwise carry -
     * "AC can never delete, even via a crafted request" has to be true here,
     * independent of the permission system.
     */
    public function canDelete(User $user, Play $play, ?int $leagueId = null): bool
    {
        if ($user->role !== 'head_coach') {
            return false;
        }

        if ($play->is_global) {
            return $leagueId !== null && $this->userCanActInLeague($user, $leagueId);
        }

        if ($play->league_id === null) {
            Log::warning('PlayAuthorizationService::canDelete - non-global play has null league_id', [
                'play_id' => $play->id,
            ]);

            return false;
        }

        return $this->userCanActInLeague($user, (int) $play->league_id);
    }

    /**
     * Whether the user may restore a hidden Global Play for the given league.
     * head_coach only, and only for a league they can act in - mirrors
     * canDelete's "head_coach only" rule for the inverse operation.
     */
    public function canRestore(User $user, int $leagueId): bool
    {
        return $user->role === 'head_coach' && $this->userCanActInLeague($user, $leagueId);
    }
}
