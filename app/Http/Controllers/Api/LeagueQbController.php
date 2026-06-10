<?php

namespace App\Http\Controllers\Api;

use App\Events\MobileSessionApproved;
use App\Events\MobileSessionLogout;
use App\Events\QbSessionUpdated;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\User;
use App\Support\LeagueOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueQbController extends Controller
{
    public function index(int $league): BaseResponse
    {
        $this->authorizeHeadCoach();
        LeagueOwnership::leagueForHeadCoach($league);

        $qbs = LeagueOwnership::qbsForLeague($league);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'qb list',
            $qbs->map(fn (User $qb) => $this->qbFields($qb))->values()->all(),
        );
    }

    public function show(int $league, int $team): BaseResponse
    {
        $this->authorizeHeadCoach();
        LeagueOwnership::teamForLeague($team, $league);

        $qb = LeagueOwnership::qbForLeagueTeam($league, $team);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'qb list',
            $qb ? [$this->qbFields($qb)] : [],
        );
    }

    public function store(Request $request, int $league, int $team): BaseResponse
    {
        $this->authorizeHeadCoach();
        LeagueOwnership::teamForLeague($team, $league);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
        ]);

        $headCoach = auth()->user();

        if (LeagueOwnership::qbForLeagueTeam($league, $team, $headCoach)) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'A QB already exists for this team.',
            );
        }

        $qb = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt('12345678'),
            'role' => 'qb',
            'head_coach_id' => $headCoach->id,
            'league_id' => $league,
            'team_id' => $team,
            'sport_id' => $headCoach->sport_id,
            'is_subscribe' => $headCoach->is_subscribe,
            'subscription_id' => $headCoach->subscription_id,
            'code' => User::generateUniqueCode(),
            'session_id' => null,
            'is_loggin' => false,
        ]);

        $headCoachRoles = $headCoach->roles->pluck('name');
        $qb->assignRole($headCoachRoles);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Add QB Successfully',
            $qb->fresh()->load('roles'),
        );
    }

    public function scanQr(Request $request, int $league, int $team): JsonResponse
    {
        $this->authorizeHeadCoach();
        LeagueOwnership::teamForLeague($team, $league);

        $request->validate([
            'session_id' => 'required|string',
        ]);

        $headCoachId = (int) auth()->id();
        $user = LeagueOwnership::qbForLeagueTeam($league, $team);

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'No QB configured for this team',
            ], 401);
        }

        $user->session_id = $request->session_id;
        $user->is_loggin = true;
        $user->save();

        $token = $user->createToken('QB-App-Token')->plainTextToken;
        $userFields = $this->qbFields($user);

        $userData = [
            'status' => 201,
            'message' => 'Login successful',
            'user' => $userFields,
            'user_id' => $user->id,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];

        broadcast(new MobileSessionApproved($userData))->toOthers();

        broadcast(new QbSessionUpdated(
            $headCoachId,
            $league,
            $userFields,
            true,
            'login',
        ))->toOthers();

        return response()->json($userData, 201);
    }

    public function logout(Request $request, int $league, int $team): JsonResponse
    {
        $this->authorizeHeadCoach();
        LeagueOwnership::teamForLeague($team, $league);

        $request->validate([
            'id' => 'required|integer|exists:users,id',
        ]);

        $coach = auth()->user();
        $user = User::query()
            ->whereKey($request->integer('id'))
            ->where('role', 'qb')
            ->where('head_coach_id', $coach->id)
            ->where('league_id', $league)
            ->where('team_id', $team)
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => 'QB user not found for this team',
            ], 404);
        }

        $sessionId = $user->session_id;
        $user->is_loggin = false;
        $user->save();

        $userFields = $this->qbFields($user);

        $payload = [
            'status' => 200,
            'message' => 'logout successful',
            'user' => $userFields,
            'is_loggin' => (bool) $user->is_loggin,
        ];

        if ($sessionId) {
            broadcast(new MobileSessionLogout([
                'status' => 200,
                'message' => 'logout successful',
                'user' => array_merge(
                    $user->only(['id', 'name', 'code', 'head_coach_id', 'league_id', 'team_id']),
                    ['session_id' => $sessionId]
                ),
            ]))->toOthers();
        }

        broadcast(new QbSessionUpdated(
            (int) $coach->id,
            $league,
            $userFields,
            false,
            'logout',
        ))->toOthers();

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function qbFields(User $user): array
    {
        return $user->only([
            'id',
            'name',
            'email',
            'role',
            'session_id',
            'code',
            'head_coach_id',
            'league_id',
            'team_id',
            'is_loggin',
        ]);
    }

    private function authorizeHeadCoach(): void
    {
        if (auth()->user()->role !== 'head_coach') {
            abort(403);
        }
    }
}
