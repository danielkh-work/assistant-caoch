<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Human Dashboard API",
 *     version="1.0.0"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Sanctum bearer token. Use format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="League QB",
 *     description="Per-team QB registration, pairing, and logout (one QB per league team per head coach)"
 * )
 *
 * @OA\Tag(
 *     name="QB Logout",
 *     description="QB application logout and session login status"
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/qb",
 *     operationId="listLeagueQbs",
 *     tags={"League QB"},
 *     summary="List all QBs for a league",
 *     description="Returns every QB user configured for teams in the given league. Each entry includes `team_id` (`league_teams.id`).",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Response(
 *         response=200,
 *         description="Zero or more QB users in `data` array",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="qb list"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=78),
 *                     @OA\Property(property="name", type="string", example="Team A QB"),
 *                     @OA\Property(property="team_id", type="integer", example=10),
 *                     @OA\Property(property="league_id", type="integer", example=22),
 *                     @OA\Property(property="head_coach_id", type="integer", example=5),
 *                     @OA\Property(property="is_loggin", type="boolean", example=false)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="League not found or not owned by the authenticated head coach")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/teams/{team}/qb",
 *     operationId="getLeagueTeamQb",
 *     tags={"League QB"},
 *     summary="Get QB for a league team",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="team", in="path", required=true, description="`league_teams.id`", @OA\Schema(type="integer", example=10)),
 *     @OA\Response(response=200, description="Zero or one QB user in `data` array")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/teams/{team}/qb",
 *     operationId="addLeagueTeamQb",
 *     tags={"League QB"},
 *     summary="Create QB for a league team",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="team", in="path", required=true, description="`league_teams.id`", @OA\Schema(type="integer", example=10)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","email"},
 *             @OA\Property(property="name", type="string", example="NFL QB"),
 *             @OA\Property(property="email", type="string", format="email", example="nfl-qb@example.com")
 *         )
 *     ),
 *     @OA\Response(response=200, description="QB created"),
 *     @OA\Response(response=422, description="A QB already exists for this team")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/teams/{team}/qb/logout",
 *     operationId="logoutLeagueTeamQb",
 *     tags={"League QB"},
 *     summary="Log out QB for a league team",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="team", in="path", required=true, description="`league_teams.id`", @OA\Schema(type="integer", example=10)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(@OA\Property(property="id", type="integer", example=78))
 *     ),
 *     @OA\Response(response=200, description="QB logged out")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/teams/{team}/web/scan-qr",
 *     operationId="scanLeagueTeamQbQr",
 *     tags={"League QB"},
 *     summary="Pair mobile session to a league team QB",
 *     description="Broadcasts session approval to `qb-user.{session_id}` and session update to `headcoach.{headCoachId}.league.{leagueId}.qb` (payload includes `team_id`). Mobile app should subscribe to `coach-group.{headCoachId}.league.{leagueId}` for match notifications.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="team", in="path", required=true, description="`league_teams.id`", @OA\Schema(type="integer", example=10)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(@OA\Property(property="session_id", type="string", format="uuid"))
 *     ),
 *     @OA\Response(response=201, description="QB paired and token issued")
 * )
 *
 * @OA\Get(
 *     path="/api/logout-qb-applicaion/{id}",
 *     operationId="logoutQbApplication",
 *     tags={"QB Logout"},
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
 *                         @OA\Property(property="head_coach_id", type="integer", nullable=true),
 *                         @OA\Property(property="league_id", type="integer", nullable=true),
 *                         @OA\Property(property="team_id", type="integer", nullable=true)
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
 *
 * @OA\Get(
 *     path="/api/qb-session-login-status/{session_id}",
 *     operationId="qbSessionLoginStatus",
 *     tags={"QB Logout"},
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
 *             @OA\Property(property="logged_in", type="boolean", description="Mirrors the QB user's `is_loggin` flag in the database.", example=true)
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
final class HumanDashboardApiDoc
{
}
