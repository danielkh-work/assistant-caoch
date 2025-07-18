<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DefensivePlay;
use App\Http\Responses\BaseResponse;
class DefensivePlayController extends Controller
{
    public function store(Request $request)
    { 
  
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
           
            'formation' => 'required|string',
            'formation' => 'required|string',
            'strategy_blitz' => 'required|string',
            'coverage_type' => 'required|string',
            'players' => 'required|json',
        
           
        ]);
        if ($request->hasFile('image')) {
                        $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                     
        }
        $defensivePlay = DefensivePlay::create([
            'name' => $validated['name'],
            'image' => $imagePath,
            'league_id' =>  $request->league_id,
            
            'formation' => $validated['formation'],
            'coverage_type' => $validated['coverage_type'],
            'strategy_blitz' => $validated['strategy_blitz'],
            'description' => $request->description,
            
           
        ]);

          $personals = json_decode($validated['players'], true);
            foreach ($personals as $player) {
                $defensivePlay->personals()->create($player); // if using hasMany
            }
           

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully", $defensivePlay);
    }
    public function index(Request $request)
    {
        $plays = DefensivePlay::with('personals')->where('league_id',$request->league_id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $plays);
    }

     public function editDefensivePlay($id)
    {
        $play = DefensivePlay::with('personals')->find($id);
        
        if ($play)
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play List", $play);
       
    }

    public function update(Request $request, $id)
    {
       
        $defensivePlay = DefensivePlay::findOrFail($id);

       
        if ($request->has('name')) {
            $defensivePlay->name = $request->name;
        }

        if ($request->has('formation')) {
            $defensivePlay->formation = $request->formation;
        }

        if ($request->has('strategy_blitz')) {
            $defensivePlay->strategy_blitz = $request->strategy_blitz;
        }

        if ($request->has('coverage_type')) {
            $defensivePlay->coverage_type = $request->coverage_type;
        }

        if ($request->has('description')) {
            $defensivePlay->description = $request->description;
        }

        if ($request->hasFile('image')) {
            $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
            $defensivePlay->image = $imagePath;
        }

        $defensivePlay->save();

     
        if (is_array($request->all())) {
        $personals = $request->all(); 
        $existingPlayerIds = [];

        foreach ($personals as $player) {
            if (!isset($player['player_id'])) {
                continue;
            }

            $existingPlayerIds[] = $player['player_id'];

            $existingPersonal = $defensivePlay->personals()
                ->where('teamplayer_id', $player['player_id'])
                ->first();

            if ($existingPersonal) {
                $existingPersonal->update([
                    'teamplayer_id' => $player['player_id'],
                    'position' => $player['position'] ?? null,
                   
                ]);
            } else {
                $defensivePlay->personals()->create([
                    'teamplayer_id' => $player['player_id'],
                    'name' => $player['customInput'] ?? null,
                   
                ]);
            }
               $defensivePlay->personals()
            ->whereNotIn('teamplayer_id', $existingPlayerIds)
            ->delete();
        }


    }


        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Defensive Play Updated Successfully", $defensivePlay);
    }
    
}
