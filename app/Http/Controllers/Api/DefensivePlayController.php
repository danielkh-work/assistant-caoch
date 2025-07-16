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
    public function index()
    {
        $plays = DefensivePlay::with('personals')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $plays);
    }

    
}
