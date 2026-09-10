<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seeds the "user type" role family (head_coach/assistant_coach/qb/performance_coach)
 * and their default permissions. Distinct from RolesAndPermissionsSeeder, which seeds
 * an unrelated family of subscription-tier roles (Classic/Pro/HD) - both families
 * coexist in the same Spatie roles/permissions tables, distinguished by `category`.
 *
 * Permission names follow a dot-delimited `entity.action` pattern so wildcard
 * permissions work (config('permission.enable_wildcard_permission') = true) -
 * e.g. `play.*` grants every play.* permission, `*` grants everything.
 */
class UserTypeRolesSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Phase 1 (foundation)
            'league.create',
            'league.edit',
            'league.transfer',
            'device.manage',
            'assistant_coach.manage',
            'qb.manage',
            'matchstart.scoreboard.override',
            'matchstart.system_suggestion.use',
            'matchstart.scoreboard.edit',
            'matchstart.scoreboard.view',
            'matchstart.position.view',
            'matchstart.events.view',
            'play.*',
            'player.*',
            'pending_user.approve',
            'role_permission.manage',
            'recruiting.view',
            '*', // head_coach's catch-all - see below

            // Phase 2: League/Team/Player/TeamGroup
            'league.view',
            'team.view',
            'team.create',
            'team.edit',
            'player.view',
            'player.create',
            'player.edit',
            'player.delete',
            'team_group.view',
            'team_group.create',
            'team_group.edit',
            'team_group.delete',

            // Phase 3: Devices/AssistantCoach/QB
            'device.view',
            'assistant_coach.view',
            'qb.view',

            // Phase 4: Plays/DefensivePlay/DefensivePlayParameter/Formation
            // (individual actions - anyone already holding the play.*/player.*
            // wildcards from Phase 1 automatically gets these too)
            'play.view',
            'play.create',
            'play.edit',
            'play.delete',
            'play.duplicate',
            'defensive_play.view',
            'defensive_play.create',
            'defensive_play.edit',
            'defensive_play.delete',
            'defensive_play.duplicate',
            'defensive_play_parameter.view',
            'defensive_play_parameter.create',
            'defensive_play_parameter.edit',
            'defensive_play_parameter.delete',
            'formation.view',
            'formation.create',
            'formation.edit',
            'formation.delete',

            // Phase 5: Game/Configure/Recruiting/PendingUser/RolePermission
            'game.view',
            'game.create',
            'game.edit',
            'game.delete',
            'game.duplicate',
            'configure.manage',

            // Phase 6: MatchStart/Scoreboard (additions beyond Foundation's
            // matchstart.scoreboard.* / .position.view / .events.view / .system_suggestion.use)
            'matchstart.substitute.manage',
            'matchstart.penalty.manage',
            'matchstart.referee.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            // head_coach gets a single wildcard rather than every permission
            // enumerated - automatically covers permissions added in later
            // phases too, matching "head coach can do everything".
            'head_coach' => ['*'],
            'assistant_coach' => [
                'matchstart.system_suggestion.use',
                'matchstart.scoreboard.edit',
                'matchstart.scoreboard.view',
                'matchstart.events.view',
                'recruiting.view',
                'league.view',
                'team.view',
                'player.view',
                'team_group.view',
                'play.view',
                'defensive_play.view',
                'formation.view',
                'game.view',
            ],
            'performance_coach' => [
                'matchstart.position.view',
                'matchstart.events.view',
                'recruiting.view',
                'league.view',
                'team.view',
                'player.view',
                'team_group.view',
                'play.view',
                'defensive_play.view',
                'formation.view',
                'game.view',
            ],
            // QB devices broadcast live play/yardage/scoreboard data during a
            // match (BroadCastScoreController::scoreBoardBroadCastQB/scoreBoardBroadCastPlay/
            // yardagePlaytoAssistant) - previously ungated (auth:sanctum only),
            // so this permission preserves existing QB capability now that
            // those routes require matchstart.scoreboard.edit.
            'qb' => [
                'matchstart.scoreboard.edit',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['category' => Role::CATEGORY_USER_TYPE]
            );
            $role->syncPermissions($rolePermissionNames);
        }
    }
}
