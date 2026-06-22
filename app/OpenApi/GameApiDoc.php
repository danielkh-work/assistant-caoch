<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Matches",
 *     description="Match lifecycle management"
 * )
 *
 * @OA\Schema(
 *     schema="MatchStartRequest",
 *     type="object",
 *     required={"league_id", "my_team_id", "oponent_team_id"},
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="my_team_id", type="integer", example=1),
 *     @OA\Property(property="oponent_team_id", type="integer", example=2),
 *     @OA\Property(
 *         property="is_practice",
 *         type="boolean",
 *         example=false,
 *         description="Optional mode flag. `true` starts a practice match, `false` starts a normal game match."
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/start-game-mode",
 *     tags={"Matches"},
 *     summary="Start a match",
 *     description="Creates a new active match session. The `is_practice` flag controls whether the session is started in practice or normal game mode. The endpoint validates league ownership, device readiness, and existing active sessions before creating the match-mode record.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/MatchStartRequest")),
 *     @OA\Response(response=200, description="Game Start SuccessFully"),
 *     @OA\Response(response=403, description="Forbidden"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Schema(
 *     schema="MatchEndRequest",
 *     type="object",
 *     @OA\Property(
 *         property="is_practice",
 *         type="boolean",
 *         example=false,
 *         description="Set to true for practice matches, false for normal game matches."
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/matches/{id}/end",
 *     tags={"Matches"},
 *     summary="End a match",
 *     description="Unified match-ending endpoint for both practice and normal matches. The `is_practice` flag controls which scoreboard table and session mode are closed. The endpoint clears match bench rows, completes active game-mode sessions, broadcasts a match-ended event, and emits the legacy scoreboard cleanup payload for the selected mode.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=36)),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/MatchEndRequest")),
 *     @OA\Response(response=200, description="Match ended successfully"),
 *     @OA\Response(response=404, description="Match not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
final class GameApiDoc
{
}
