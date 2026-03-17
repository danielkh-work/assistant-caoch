<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Player;
class TeamPlayerPositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Offensive positions
        $offensivePositions = [
            'Quarterback',
            'Running Back',
            'Fullback',
            'Wide Receiver X',
            'Wide Receiver Z',
            'Slot W',
            'Slot Y',
            'Tight End H',
            'Tight End F',
            'Left Tackle',
            'Right Tackle',
            'Left Guard',
            'Right Guard',
            'Center',
            'Halfback',
        ];

        // Defensive positions
        $defensivePositions = [
            'Defensive Tackle',
            'Nose Tackle',
            'Defensive End',
            'Edge Rusher Quick',
            'Edge Rusher Rush',
            'Middle Linebacker (Mike)',
            'Strong Linebacker (Sam)',
            'Weak Side Linebacker (Will)',
            'Add Dime',
            'Dollar',
            'Extras',
            '1/2 Back',
            'Outside Linebacker',
            'Inside Linebacker',
            'Corner Weak Side',
            'Corner Strong Side',
            'Nickelback',
            'Strong Safety',
            'Free Safety',
            'Halfback Weak Side',
            'Halfback Strong Side',
        ];
        
       // DB::statement('USE dkseugik_humandashboard');
        $players = DB::table('team_players')->get();
       
        // DB::connection('mysql')->table('players')->get();
      
        foreach ($players as $player) {

            // Determine the position pool based on type
            $positionsPool = [];
            if (strtolower($player->position ?? '') === 'offence') {
                $positionsPool = $offensivePositions;
            } elseif (strtolower($player->position ?? '') === 'deffence') {
                $positionsPool = $defensivePositions;
            }

            if (empty($positionsPool)) continue; // skip if type not defined

            // Random number of positions: 1 to 5
            $numPositions = rand(1, min(5, count($positionsPool)));

            // Shuffle positions and pick the first N
            shuffle($positionsPool);
            $selectedPositions = array_slice($positionsPool, 0, $numPositions);

            foreach ($selectedPositions as $index => $positionName) {
               // DB::statement('USE dkseugik_humandashboard');
                DB::table('team_player_positions')->insert([
                    'teamplayer_id' => $player->id,
                    'position_name' => $positionName,
                    'meta' => null,
                    'sort' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Random player positions (1–5) seeded for all players!');
    }
}
