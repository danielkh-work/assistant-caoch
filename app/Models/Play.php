<?php

namespace App\Models;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Play extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * These children have a DB-level cascade-on-delete FK pointing at plays, which
     * only fires on a real DELETE. Now that plays soft-deletes (an UPDATE), that
     * cascade never fires, so this replicates the exact same cleanup by hand -
     * same end result as before, just triggered from code instead of the FK.
     */
    public function cascadeDeleteChildren(): void
    {
        PlayTargetOffensivePlayer::where('play_id', $this->id)->delete();
        PlayTargetDefensivePlayer::where('play_id', $this->id)->delete();
        OffensiveTargetStrength::where('play_id', $this->id)->delete();
        ConfigurePlay::where('play_id', $this->id)->delete();
        DB::table('personal_grouping_play')->where('play_id', $this->id)->delete();
    }
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

 