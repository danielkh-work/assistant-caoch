<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\DefensivePlayParameter;
use App\Http\Responses\BaseResponse;
use App\Http\Controllers\Controller;


class DefensivePlayParameterController extends Controller
{
        public function index($id)
    {  
      
        $play = DefensivePlayParameter::where('league_id', $id)->get();
       
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $play);
    }

    public function store(Request $request)
    { 
        
        $validated = $request->validate([
          
            'formation' => 'required',
            'blitz_packages' => 'required', 
        ]);
        $validated['league_id']=$request->league_id;
        $validated['user_id'] = auth()->id();
        $validated['description']=$request->description;
        $parameter = DefensivePlayParameter::create($validated);
        return response()->json($parameter, 201);
    }

    public function update(Request $request, $id)
    {
        $parameter = DefensivePlayParameter::findOrFail($id);

        $validated = $request->validate([
            'formation' => 'required',
            'blitz_packages' => 'required',
           
        ]);
        $validated['description']=$request->description;
        $parameter->update($validated);

        return response()->json($parameter);
    }

     public function edit($id)
    {
        $play = DefensivePlayParameter::findOrFail($id);
        if ($play)
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play List", $play);
       
    }

    public function delete($id)
    {
        $parameter = DefensivePlayParameter::findOrFail($id);
        $parameter->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
