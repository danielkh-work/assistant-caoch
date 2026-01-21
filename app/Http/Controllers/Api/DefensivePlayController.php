<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DefensivePlay;
use App\Models\DefensivePlayPersonal;
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
            'coverage_category' => 'required|string',
           
          
        
           
        ]);
        if ($request->hasFile('image')) {
                        $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                     
        }
        $defensivePlay = DefensivePlay::create([
            'name' => $validated['name'],
            'image' => $imagePath,
            'league_id' =>  $request->league_id,
            'opponent_personnel_grouping' =>  $request->opponent_personnel_grouping,
            'min_expected_yard' =>  $request->min_expected_yard,
            'preferred_down' =>  $request->preferred_down,
            'strategies' =>  $request->strategies,
            'formation' => $validated['formation'],
            'strategy_blitz' => $validated['strategy_blitz'],
            'coverage_category' => $validated['coverage_category'],
            'description' => $request->description,
            
           
        ]);


          $groups = $request->input('groups', []);
            if (!is_array($groups)) {
                $groups = [];
            }
            $groups = array_filter($groups, fn($g) => !is_null($g) && $g !== '');

            if (!empty($groups)) {
                    $defensivePlay->personalGroupings()->sync([1,2,3]);
            }
        
       
         
         if ($request->has('opponentPlayers') && is_array($request->opponentPlayers)) {
            foreach ($request->opponentPlayers as $player) {
                if (!empty($player['id'])) {
                    DefensivePlayPersonal::create([
                        'defensive_play_id' => $defensivePlay->id,
                        'teamplayer_id' => $player['id'],
                     
                        'name' =>'name',
                    ]);
                }
            }
        }

    
           

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully", $defensivePlay);
    }
    public function index(Request $request)
    {
        
        $plays = DefensivePlay::with('playResults','strategyBlitz','formation','personals.teamPlayer.player')->where('league_id',$request->league_id)
            ->withCount([
            'playResults as win_result' => function ($q) {
            $q->where('result', 'win');
            },
            'playResults as loss_result' => function ($q) {
            $q->where('result', 'loss');
            },
            'playResults as total_count'
            ])
             ->withAvg('playResults as yardage_difference', 'yardage_difference') 
        ->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $plays);
    }

     public function editDefensivePlay($id)
    {
        $play = DefensivePlay::with('personals')->find($id);
        
        if ($play)
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play List", $play);
       
    }

    public function delete($id)
    {
        $parameter = DefensivePlay::findOrFail($id);
        $parameter->delete();

        return response()->json(['message' => 'Deleted']);
    }

public function update(Request $request, $id)
{
  
    // \Log::info($request->all()); // parsed fields
    // \Log::info($request->getContent()); // raw payload
  
    $defensivePlay = DefensivePlay::findOrFail($id);

    // Replace image if uploaded
    if ($request->hasFile('image')) {
        $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
        $defensivePlay->image = $imagePath;
    }
    if ($request->filled('name')) {
        $defensivePlay->name = $request->name;
    }
     if ($request->filled('opponent_personnel_grouping')) {
      $defensivePlay->opponent_personnel_grouping = $request->opponent_personnel_grouping;
    }
    if ($request->filled('formation')) {
      $defensivePlay->formation = $request->formation;
    }
    if ($request->filled('strategy_blitz')) {
      $defensivePlay->strategy_blitz = $request->strategy_blitz;
    }
        if ($request->filled('min_expected_yard')) {
        $defensivePlay->min_expected_yard = $request->min_expected_yard;
    }

    if ($request->filled('preferred_down')) {
        $defensivePlay->preferred_down = $request->preferred_down;
    }

    if ($request->filled('strategies')) {
        $defensivePlay->strategies = $request->strategies;
    }
     if ($request->filled('description')) {
      $defensivePlay->description = $request->description;
    }
     

     $defensivePlay->save();

    // Delete old personals
    DefensivePlayPersonal::where('defensive_play_id', $defensivePlay->id)->delete();

    // Recreate personals
    if ($request->has('opponentPlayers') && is_array($request->opponentPlayers)) {
        foreach ($request->opponentPlayers as $player) {
            if (!empty($player['id'])) {
                DefensivePlayPersonal::create([
                    'defensive_play_id' => $defensivePlay->id,
                    'teamplayer_id' => $player['id'],
                    'name' => 'name', // optional: replace with real name from request
                ]);
            }
        }
    }

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Play updated successfully",
        $defensivePlay
    );
}


 public function duplicateDefensivePlay($id){


     $play = DefensivePlay::findOrFail($id);

    $newPlay = $play->replicate();

    // If you want to tweak some fields (like name to avoid conflict)
    $newPlay->name = $play->name . ' (Copy)';

    // Save the new record
    $newPlay->save();

    // If it has related personals and you want to duplicate them too
    foreach ($play->personals as $personal) {
        $newPersonal = $personal->replicate();
        $newPersonal->defensive_play_id = $newPlay->id;
        $newPersonal->save();
    }

   return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "play clone successfully",
        $newPlay
    );

 }

    
}
