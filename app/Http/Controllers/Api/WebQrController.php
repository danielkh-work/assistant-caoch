<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSession;
use App\Models\User;
use App\Events\MobileSessionApproved;
use App\Events\MobileSessionLogout;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Human Dashboard",
 *     description="QB session flows (Human Dashboard Postman collection)"
 * )
 */
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
            'user'         => $user->only(['name', 'session_id', 'code', 'head_coach_id']),
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ];

        broadcast(new MobileSessionApproved($userData))->toOthers();

        return response()->json($userData, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/logout-qb-applicaion/{id}",
     *     operationId="logoutQbApplication",
     *     tags={"Human Dashboard"},
     *     summary="QB application logout",
     *     description="Clears `session_id` and `is_loggin` for the QB user. Postman: **QB Logout Success**.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="QB user primary key",
     *         @OA\Schema(type="integer", example=78)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logout succeeded, or user was not found (see `status` and `message` in the JSON body; HTTP code may still be 200).",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="integer", example=200),
     *                     @OA\Property(property="message", type="string", example="logout successful"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="session_id", type="string", nullable=true),
     *                         @OA\Property(property="code", type="string", nullable=true),
     *                         @OA\Property(property="head_coach_id", type="integer", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="integer", example=404),
     *                     @OA\Property(property="message", type="string", example="User not found")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
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
            'user'    => $user->only(['name', 'session_id', 'code', 'head_coach_id']),
        ];

        return response()->json($userData);
    }

    /**
     * @OA\Get(
     *     path="/api/qb-session-login-status/{session_id}",
     *     operationId="qbSessionLoginStatus",
     *     tags={"Human Dashboard"},
     *     summary="Check QB login status",
     *     description="Returns **200** when a QB user is bound to the given mobile session UUID. Returns **401** when no QB has this `session_id` (Postman **Check QB Login Status 200** / invalid session **Check QB Login Status 403** — API uses **401 Unauthenticated**).",
     *     @OA\Parameter(
     *         name="session_id",
     *         in="path",
     *         required=true,
     *         description="Mobile pairing session UUID from `POST /api/mobile/create-session`",
     *         @OA\Schema(type="string", format="uuid", example="822fc835-75aa-48bf-8473-354a4913aab2")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QB is linked to this session",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="session_id", type="string", format="uuid"),
     *             @OA\Property(property="logged_in", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated — no QB user with this session_id",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
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
            'logged_in' => true,
        ]);
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
