<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
use App\Models\User;
use App\Events\MobileSessionApproved;
use App\Events\QbSessionUpdated;
use App\Support\QbLogoutBroadcaster;
use App\Support\QbMobileSession;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class WebQrController extends Controller
{
    // Mobile generates session
    public function createSession(Request $request)
    {
        $sessionId = Str::uuid()->toString();

        if (\Illuminate\Support\Facades\Schema::hasTable('mobile_sessions')) {
            MobileSession::create([
                'mobile_user_id' => null,
                'session_id' => $sessionId,
                'status' => 'pending',
            ]);
        }

        // Returning QB: app opens create-session, subscribes qb-logout.{sessionId}, keeps bearer token.
        $qb = $this->optionalSanctumUser($request);
        if ($qb && $qb->role === 'qb' && $qb->is_loggin) {
            QbMobileSession::refreshActiveSession($qb, $sessionId);
        }

        return response()->json([
            'session_id' => $sessionId,
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

        QbMobileSession::bind($user, $request->session_id);
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
            (int) ($user->league_id ?? 0),
            $user->only(['id', 'name', 'email', 'session_id', 'code', 'head_coach_id', 'league_id', 'team_id', 'is_loggin']),
            true,
            'login',
        ))->toOthers();

        return response()->json($userData, 201);
    }

    public function logouQbApplicaion(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ]);
        }

        $coach = $user->head_coach_id
            ? User::find($user->head_coach_id)
            : null;

        if ($coach && $user->role === 'qb') {
            return response()->json(QbLogoutBroadcaster::logoutAndBroadcast($user, $coach));
        }

        $user->session_id = null;
        $user->is_loggin = false;
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => 'logout successful',
            'user' => $user->only(['id', 'name', 'session_id', 'code', 'head_coach_id', 'is_loggin']),
        ]);
    }

    public function qbSessionLoginStatus(Request $request, string $session_id)
    {
        $user = User::where('session_id', $session_id)
            ->where('role', 'qb')
            ->first();

        if ($user === null) {
            $tokenUser = $this->optionalSanctumUser($request);
            if ($tokenUser && $tokenUser->role === 'qb' && $tokenUser->is_loggin) {
                $user = QbMobileSession::refreshActiveSession($tokenUser, $session_id);
            }
        }

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
            'session_id' => 'sometimes|nullable|string|uuid',
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

        return response()->json(QbLogoutBroadcaster::logoutAndBroadcast(
            $user,
            $coach,
            array_filter([$request->input('session_id')]),
        ));
    }

    private function optionalSanctumUser(Request $request): ?User
    {
        $user = $request->user('sanctum');
        if ($user instanceof User) {
            return $user;
        }

        $token = $request->bearerToken();
        if (! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }


 
}
