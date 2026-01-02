<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class QrToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'token', 'used'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}


// HeadCoachController.php
// use App\Models\User;
// use Illuminate\Support\Str;

// public function createAssistant(Request $request)
// {
//     $request->validate([
//         'name' => 'required',
//         'email' => 'required|email|unique:users,email'
//     ]);

//     $assistant = User::create([
//         'name' => $request->name,
//         'email' => $request->email,
//         'role' => 'assistant_coach',
//         'password' => bcrypt(Str::random(20)), // temporary password, never used
//         'head_coach_id' => auth()->id(),
//     ]);

//     return redirect()->back()->with('success', 'Assistant created successfully. Mobile QR login will be used.');
// }



// QrLoginController.php
// use App\Models\QrToken;
// use Illuminate\Support\Str;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;

// public function generateQrForAssistant(User $assistant)
// {
//     if ($assistant->role !== 'assistant_coach') {
//         abort(403);
//     }

//     // Generate a single-use token
//     $token = Str::random(40);

//     QrToken::create([
//         'user_id' => $assistant->id,
//         'token' => $token,
//         'used' => false,
//     ]);

//     // Generate QR pattern
//     $qrCode = QrCode::size(250)->generate($token);

//     return response()->json([
//         'qr_code' => $qrCode,
//         'token' => $token
//     ]);
// }




// use App\Models\QrToken;
// use Illuminate\Support\Facades\Auth;

// public function loginViaQr(Request $request)
// {
//     $request->validate(['token' => 'required|string']);

//     $qrToken = QrToken::where('token', $request->token)
//         ->where('used', false)
//         ->first();

//     if (!$qrToken) {
//         return response()->json(['error' => 'Invalid or used token'], 401);
//     }

//     $user = $qrToken->user;

//     if ($user->role !== 'assistant_coach') {
//         return response()->json(['error' => 'Not allowed'], 403);
//     }

//     // Mark token as used
//     $qrToken->update(['used' => true]);

//     // Log in assistant (mobile session)
//     Auth::login($user);

//     return response()->json([
//         'success' => true,
//         'user' => $user
//     ]);
// }


