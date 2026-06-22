<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TeamGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'type',
        'segment',
        'status',
    ];

    protected $appends = [
        'group_name',
        'group_level',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (TeamGroup $group) {

            $validSegments = [7, 11, 12];

            // Force inactive if segment is invalid
            if (! in_array((int) $group->segment, $validSegments, true)) {
                $group->status = 'inactive';

                return;
            }

            if ($group->status === 'active') {

                $group->ensureValidActivation();
            }
        });
    }

    /**
     * Call this from service/controller after sync.
     */
    public function validatePlayerCountOrDeactivate(): void
    {
        $required = (int) $this->segment;

        if ($this->players()->count() !== $required) {
            $this->updateQuietly([
                'status' => 'inactive',
            ]);
        }
    }

    /**
     * Strict activation rule
     */
    public function ensureValidActivation(): void
    {
        $required = (int) $this->segment;

        $current = $this->players()->count();

        if ($current !== $required) {
            throw ValidationException::withMessages([
                'status' => "Cannot activate group. Required players: {$required}, current: {$current}.",
            ]);
        }
    }

    public function team()
    {
        return $this->belongsTo(LeagueTeam::class, 'team_id');
    }

    public function players()
    {
        return $this->belongsToMany(
            TeamPlayer::class,
            'team_group_player',
            'team_group_id',
            'team_player_id'
        )->withTimestamps();
    }

    public function configuredTeams()
    {
        return $this->belongsToMany(
            LeagueTeam::class,
            'team_group_configurations',
            'team_group_id',
            'team_id'
        )->withTimestamps();
    }

    public function getGroupNameAttribute(): string
    {
        return (string) $this->name;
    }

    public function getGroupLevelAttribute(): ?int
    {
        return $this->segment !== null ? (int) $this->segment : null;
    }
}
