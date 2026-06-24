<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LeagueTeam extends Model
{
    use HasFactory;
    public function teamplayer()
    {
        return $this->hasMany(TeamPlayer::class,'team_id');
    }
    public function league()
    {
        return $this->belongTo(League::class,'league_id');
    }

    public function qb()
    {
        return $this->hasOne(User::class, 'team_id')->where('role', 'qb');
    }
    public function practiceTeamplayer()
    {
        return $this->hasMany(PracticeTeamPlayer::class,'team_id');
    }

    public function groups()
    {
        return $this->hasMany(TeamGroup::class, 'team_id');
    }

    public function configuredGroups()
    {
        return $this->belongsToMany(
            TeamGroup::class,
            'team_group_configurations',
            'team_id',
            'team_group_id'
        )->withTimestamps();
    }

    /**
     * @return array<int,int>
     */
    public function configuredPlayerIds(): array
    {
        if (! Schema::hasTable('team_group_configurations')) {
            return [];
        }

        $this->loadMissing(['configuredGroups.players']);

        return $this->configuredGroups
            ->filter(fn (TeamGroup $group) => strtolower((string) $group->status) === 'active')
            ->flatMap(fn (TeamGroup $group) => $group->players)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }



    
}
