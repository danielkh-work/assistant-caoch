<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames the initial permission set to a systematic `entity.action` naming
 * pattern (needed now that wildcard permissions are enabled, which rely on
 * dot-delimited segments). Renaming in place - not delete+recreate - so
 * role_has_permissions / model_has_permissions / user_denied_permissions
 * (all keyed by permission_id, not name) keep every existing assignment.
 */
return new class extends Migration
{
    private array $renames = [
        'create_league' => 'league.create',
        'edit_league_settings' => 'league.edit',
        'transfer_league' => 'league.transfer',
        'manage_devices' => 'device.manage',
        'manage_assistant_coaches' => 'assistant_coach.manage',
        'manage_qb_users' => 'qb.manage',
        'override_scoreboard' => 'matchstart.scoreboard.override',
        'use_system_suggestion' => 'matchstart.system_suggestion.use',
        'edit_scoreboard_live' => 'matchstart.scoreboard.edit',
        'view_scoreboard_tab' => 'matchstart.scoreboard.view',
        'view_position_tab' => 'matchstart.position.view',
        'view_events_tab' => 'matchstart.events.view',
        // These two become wildcards rather than plain `.manage` names, since
        // finer play.*/player.* permissions are added in a later phase and
        // should automatically be covered by whatever already held the
        // "manage everything about plays/roster" permission.
        'manage_upload_plays' => 'play.*',
        'manage_player_roster' => 'player.*',
        'approve_pending_users' => 'pending_user.approve',
        'manage_roles_permissions' => 'role_permission.manage',
        'view_league_recruiting' => 'recruiting.view',
    ];

    public function up(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('permissions')->where('name', $old)->update(['name' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('permissions')->where('name', $new)->update(['name' => $old]);
        }
    }
};
