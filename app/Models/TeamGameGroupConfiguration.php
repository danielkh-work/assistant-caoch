<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamGameGroupConfiguration extends Model
{
    use HasFactory;

    protected $table = 'team_game_group_configurations';

    protected $fillable = [
        'game_id',
        'team_id',
        'group_id',
    ];

    /**
     * Get the group that belongs to this configuration.
     */
    public function group()
    {
        return $this->belongsTo(PersionalGrouping::class, 'group_id');
    }

    /**
     * Get the game that belongs to this configuration.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Get the team that belongs to this configuration.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get all selected group IDs for a specific game and team.
     *
     * @param int $gameId
     * @param int $teamId
     * @return array<int, int>
     */
    public static function getSelectedGroupIds(int $gameId, int $teamId): array
    {
        return self::query()
            ->where('game_id', $gameId)
            ->where('team_id', $teamId)
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Update selected groups for a specific game and team.
     * This replaces all existing configurations with the new selection.
     *
     * @param int $gameId
     * @param int $teamId
     * @param array<int, int> $groupIds
     * @return void
     */
    public static function updateSelectedGroups(int $gameId, int $teamId, array $groupIds): void
    {
        // Delete existing configurations for this game and team
        self::query()
            ->where('game_id', $gameId)
            ->where('team_id', $teamId)
            ->delete();

        // Insert new configurations
        if (!empty($groupIds)) {
            $insertData = array_map(function ($groupId) use ($gameId, $teamId) {
                return [
                    'game_id' => $gameId,
                    'team_id' => $teamId,
                    'group_id' => (int) $groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $groupIds);

            self::insert($insertData);
        }
    }
}
