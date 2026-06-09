<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
use App\Models\User;
use App\Events\MobileSessionApproved;
use App\Events\MobileSessionLogout;
use App\Events\QbSessionUpdated;
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
            'session_id' => 'required|string',
            'qb_id'      => 'sometimes|nullable|integer|exists:users,id',
        ]);

        $authId = auth()->id();

        $qbQuery = User::query()
            ->where('role', 'qb')
            ->where('head_coach_id', $authId);

        if ($request->filled('qb_id')) {
            $user = (clone $qbQuery)->whereKey($request->integer('qb_id'))->first();
        } else {
            $qbCount = (clone $qbQuery)->count();
            if ($qbCount === 0) {
                return response()->json([
                    'status' => 401,
                    'message' => 'No QB users found for this coach',
                ], 401);
            }
            if ($qbCount > 1) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Multiple QBs linked to this coach; pass qb_id to choose which QB to pair.',
                ], 422);
            }
            $user = $qbQuery->orderByDesc('id')->first();
        }

        \Log::info(['qb user' => $user]);

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid or unauthorized QB user',
            ], 401);
        }

        $user->session_id = $request->session_id;
        $user->is_loggin = true;
        $user->save();

        $token = $user->createToken('QB-App-Token')->plainTextToken;

        $userData = [
            'status'       => 201,
            'message'      => 'Login successful',
            'user'         => $user->only(['id', 'name', 'session_id', 'code', 'head_coach_id']),
            'user_id'      => $user->id,
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ];

        broadcast(new MobileSessionApproved($userData))->toOthers();

        broadcast(new QbSessionUpdated(
            (int) $authId,
            $user->only(['id', 'name', 'email', 'session_id', 'code', 'head_coach_id', 'is_loggin']),
            true,
            'login',
        ))->toOthers();

        return response()->json($userData, 201);
    }

    public function logouQbApplicaion(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ]);
        }

        $user->session_id = null;
        $user->is_loggin = false;
        $user->save();

        $userData = [
            'status'  => 200,
            'message' => 'logout successful',
            'user'    => $user->only(['id', 'name', 'session_id', 'code', 'head_coach_id', 'is_loggin']),
        ];

        if ($user->head_coach_id && $user->league_id) {
            broadcast(new QbSessionUpdated(
                (int) $user->head_coach_id,
                (int) $user->league_id,
                $userData['user'],
                false,
                'logout',
            ))->toOthers();
        }

        return response()->json($userData);
    }

    public function qbSessionLoginStatus(string $session_id)
    {
        $user = User::where('session_id', $session_id)
            ->where('role', 'qb')
            ->first();

        if ($user === null) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'status' => 200,
            'session_id' => $session_id,
            'logged_in' => (bool) $user->is_loggin,
        ]);
    }

    public function logoutQb(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
        ]);

        $coach = $request->user();
        if ($coach->role !== 'head_coach') {
            return response()->json([
                'status' => 403,
                'message' => 'Forbidden',
            ], 403);
        }

        $user = User::query()
            ->whereKey($request->integer('id'))
            ->where('role', 'qb')
            ->where('head_coach_id', $coach->id)
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => 'QB user not found',
            ], 404);
        }

        $sessionId = $user->session_id;

        $user->is_loggin = false;
        $user->save();

        $payload = [
            'status' => 200,
            'message' => 'logout successful',
            'user' => $user->only(['id', 'name', 'session_id', 'code', 'head_coach_id']),
            'is_loggin' => (bool) $user->is_loggin,
        ];

        if ($sessionId) {
            broadcast(new MobileSessionLogout([
                'status' => 200,
                'message' => 'logout successful',
                'user' => array_merge(
                    $user->only(['id', 'name', 'code', 'head_coach_id']),
                    ['session_id' => $sessionId]
                ),
            ]))->toOthers();
        }

        broadcast(new QbSessionUpdated(
            (int) $coach->id,
            $user->only(['id', 'name', 'email', 'session_id', 'code', 'head_coach_id', 'is_loggin']),
            false,
            'logout',
        ))->toOthers();

        return response()->json($payload);
    }


 
}
