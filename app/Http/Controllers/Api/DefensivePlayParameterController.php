<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\DefensivePlayParameter;
use App\Http\Responses\BaseResponse;
use App\Http\Controllers\Controller;


class DefensivePlayParameterController extends Controller
{
        public function index()
    {
        $play = DefensivePlayParameter::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $play);
    }

    public function store(Request $request)
    { 
        \Log::info(['data'=>$request->all()]);
        $validated = $request->validate([
          
            'formation' => 'required',
            'blitz_packages' => 'required', 
        ]);
        $validated['league_id']=$request->league_id;
        $validated['user_id'] = auth()->id();
        $parameter = DefensivePlayParameter::create($validated);
        return response()->json($parameter, 201);
    }

    public function update(Request $request, $id)
    {
        $parameter = DefensivePlayParameter::findOrFail($id);

        $validated = $request->validate([
            'parameter_name' => 'sometimes|required|string|max:255',
            'value' => 'nullable|string|max:255',
        ]);

        $parameter->update($validated);

        return response()->json($parameter);
    }

    public function destroy($id)
    {
        $parameter = DefensivePlayParameter::findOrFail($id);
        $parameter->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
