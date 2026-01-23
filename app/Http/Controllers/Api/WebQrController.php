<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
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

    // Web scans QR
    public function scanQr(Request $request)
    {
        $request->validate(['session_id' => 'required|uuid']);

        $session = MobileSession::where('session_id', $request->session_id)
            ->where('status', 'pending')
            ->firstOrFail();

        $session->update(['status' => 'approved']);

        // Broadcast event to mobile
        broadcast(new MobileSessionApproved($session))->toOthers();

        return response()->json(['message' => 'Session approved']);
    }
}
