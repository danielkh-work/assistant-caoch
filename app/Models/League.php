<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class League extends Model
{
    use HasFactory;

    protected $appends = ['sport_name', 'rules_name']; // Correct spelling

    public function teams()
    {
        return $this->hasMany(LeagueTeam::class,'league_id');
    }

    public function accessGrants()
    {
        return $this->hasMany(LeagueAccess::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'league_access')
            ->withPivot('access_type')
            ->withTimestamps();
    }

    /**
     * Leagues visible to the authenticated user.
     * Owners are stored on leagues.user_id; future sharing uses league_access.
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if (in_array($user->role, ['assistant_coach', 'performance_coach'], true)) {
            return $query->where('user_id', $user->head_coach_id);
        }

        return $query->where(function (Builder $ownerOrShared) use ($user) {
            $ownerOrShared->where('user_id', $user->id)
                ->orWhereHas('accessGrants', function (Builder $grant) use ($user) {
                    $grant->where('user_id', $user->id);
                });
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if (in_array($user->role, ['assistant_coach', 'performance_coach'], true)) {
            return (int) $this->user_id === (int) $user->head_coach_id;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $this->accessGrants()->where('user_id', $user->id)->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
    public function league_rule()
    {
        return $this->belongsTo(LeagueRule::class,'league_rule_id');
    }
    public function sport()
    {
        return $this->belongsTo(Sport::class,'sport_id');
    }

    public function getSportNameAttribute()
    {
        return $this->sport ? $this->sport->title : '-';
    }

    public function getRulesNameAttribute()
    {
        return $this->league_rule ? $this->league_rule->title : '-';
    }

    public function matches()
    {
        return $this->hasMany(PlayGameMode::class,'league_id');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }
}
