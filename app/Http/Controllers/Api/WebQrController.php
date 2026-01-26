<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
use App\Models\User;
use App\Events\MobileSessionApproved;
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

       $user = User::where('role', 'qb')
                    ->first();
        
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

        $userData = $user->only(['name', 'session_id', 'code','head_coach_id']);   
        \Log::info(['scan with the qrcode mobile user data'=> $userData]);  
        
        broadcast(new MobileSessionApproved($userData))->toOthers();

       
    }


 
}
