<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
use App\Models\User;
use App\Events\MobileSessionApproved;
use App\Events\MobileSessionLogout;
use Illuminate\Support\Str;

class WebQrController extends Controller
{
    // Mobile generates session
    public function createSession(Request $request)
    {
        // $user = $request->user(); // mobile user authenticated

        $session = MobileSession::create([
            'mobile_user_id' => null,
            'session_id' => Str::uuid()->toString(),
        ]);

        return response()->json([
            'session_id' => $session->session_id,
        ]);
    }

  
    public function scanQr(Request $request)
    {
        $request->validate([
            'session_id' => 'string',   
        ]);
       $authId = auth()->id();
       $user = User::where('head_coach_id', $authId)->where('role', 'qb')
                    ->first();
       \Log::info(['qb user'=>$user]);             
         if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid code'
            ], 401);
        }
        if ($request->filled('session_id')) {
            $user->session_id = $request->session_id;
            $user->save();
        }
    
        $token = $user->createToken('QB-App-Token')->plainTextToken;

        // $userData = $user->only(['name', 'session_id', 'code','head_coach_id']);   
        
        $userData = [
            'status'       => 201,
            'message'      => 'Login successful',
            'user'         => $user->only(['name', 'session_id', 'code','head_coach_id']),
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ];
        
        broadcast(new MobileSessionApproved($userData))->toOthers();
    }

    public function logoutQb(Request $request){
       

        $user = User::find($request->id);
        $token = $user->createToken('QB-App-Token')->plainTextToken;
        $userData = [
            'status'       => 201,
            'message'      => 'logout successful',
            'user'         => $user->only(['name', 'session_id', 'code','head_coach_id']),
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ];
      
        
        broadcast(new MobileSessionLogout($userData))->toOthers();
    }


 
}
