<?php

namespace App\Models;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Play extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes - needed for Play::create([...]) call sites
     * (e.g. PlayAuthorizationServiceTest, and Tasks 5-8's test fixtures).
     * Historically this model was only ever populated via
     * `new Play(); $play->field = ...; $play->save();` (see PlayController),
     * so no $fillable/$guarded was ever declared; listing the specific
     * columns here is additive and doesn't change any existing create/update
     * behavior - nothing in the codebase used Play::create() before this.
     */
    protected $fillable = [
        'league_id', 'is_global', 'created_by', 'created_by_user_id',
        'play_name', 'play_type', 'zone_selection',
        'min_expected_yard', 'max_expected_yard',
        'pre_snap_motion', 'play_action_fake',
        'possession', 'video_path',
    ];

    public function configuredLeagues()
    {
        return $this->belongsToMany(League::class, 'configure_plays', 'play_id', 'league_id');
    }

    public function offensiveTargets()
    {
        return $this->hasMany(OffensiveTargetStrength::class, 'play_id');
    }

    public function targetOffensivePlayers()
    {
        return $this->hasMany(PlayTargetOffensivePlayer::class, 'play_id');
    }

    public function offensivePositions()
    {
        return $this->belongsToMany(OffensivePosition::class, 'play_target_offensive_players', 'play_id', 'offensive_position_id')->withPivot('strength');
    }
     public function deffensivePositions()
    {
        return $this->belongsToMany(DefensivePosition::class, 'play_target_defensive_players', 'play_id', 'defensive_position_id')->withPivot('strength');
    }
      public function playResults()
    {
        return $this->hasMany(PlayResult::class);
    }

    /** Rows in league_play_overrides where THIS play is the global original being hidden/customized. */
    public function overrides()
    {
        return $this->hasMany(LeaguePlayOverride::class, 'global_play_id');
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }

    public function personalGroupings()
    {
        return $this->belongsToMany(
            PersionalGrouping::class,
            'personal_grouping_play',
            'play_id',
            'personal_grouping_id'
        );
    }

    public function teamGroups()
    {
        return $this->belongsToMany(
            TeamGroup::class,
            'team_group_play',
            'play_id',
            'team_group_id'
        );
    }
}

 