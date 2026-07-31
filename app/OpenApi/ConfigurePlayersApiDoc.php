<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Configure Players",
 *     description="Pre-game / in-match roster configuration for our team (team_type=1)."
 * )
 *
 * @OA\Schema(
 *     schema="ConfigurePlayerSuccessData",
 *     type="object",
 *     @OA\Property(
 *         property="removed_player_ids",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         example={42, 55},
 *         description="Player IDs that were on the previous roster but omitted from this save."
 *     ),
 *     @OA\Property(
 *         property="removal_logged",
 *         type="boolean",
 *         example=true,
 *         description="True when the match was live and a play_game_logs row (type_of_log=player_removed) was created."
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ConfigurePlayerSuccessResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="configure Player successFully"),
 *     @OA\Property(property="data", ref="#/components/schemas/ConfigurePlayerSuccessData")
 * )
 *
 * @OA\Schema(
 *     schema="ConfigurePlayerLiveRemovalErrorResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=422),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="A reason is required to remove players while the match is in progress."
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/configure-player",
 *     operationId="configurePlayerStore",
 *     tags={"Configure Players"},
 *     summary="Replace our-team match roster (configure players)",
 *     description="Full roster replace for our team (team_type=1). Omit a previously configured player_id to remove them.

**Live match behaviour:**
- Backend detects live via websocket scoreboard for this `match_id` (`is_start=true`, action != EndMatch, active PlayGameMode).
- If the match is live AND any players are removed AND `removal_reason` is missing/empty → **422** (roster not saved).
- If the match is live AND players are removed AND `removal_reason` is provided → roster is saved and a `play_game_logs` event is created with `type_of_log=player_removed`, `reasons=removal_reason`, `players_out=[...]`, then broadcast on MatchLogCreated.
- If the match is NOT live → `removal_reason` is optional/ignored; normal save (no event log).",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"team_id","match_id","game_type"},
 *                 @OA\Property(property="team_id", type="integer", example=121),
 *                 @OA\Property(property="match_id", type="integer", example=81, description="Scheduled games.id"),
 *                 @OA\Property(property="game_type", type="integer", enum={1, 2}, example=1, description="1=regular game, 2=practice"),
 *                 @OA\Property(property="number_of_players", type="integer", nullable=true, example=11),
 *                 @OA\Property(
 *                     property="player_id",
 *                     type="array",
 *                     description="Remaining roster player IDs (TeamPlayer ids for game_type=1, PracticeTeamPlayer ids for game_type=2). Omitted previous IDs are removals.",
 *                     @OA\Items(type="integer")
 *                 ),
 *                 @OA\Property(
 *                     property="type",
 *                     type="array",
 *                     description="Parallel array to player_id: offensive|defensive|special",
 *                     @OA\Items(type="string", enum={"offensive","defensive","special"})
 *                 ),
 *                 @OA\Property(
 *                     property="removal_reason",
 *                     type="string",
 *                     nullable=true,
 *                     example="Injured — left field in Q2",
 *                     description="Required when match is live and at least one player is removed. Ignored when match is not live."
 *                 ),
 *                 @OA\Property(property="image", type="string", format="binary", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Roster saved",
 *         @OA\JsonContent(ref="#/components/schemas/ConfigurePlayerSuccessResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation / live removal without reason",
 *         @OA\JsonContent(ref="#/components/schemas/ConfigurePlayerLiveRemovalErrorResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Schema(
 *     schema="LiveScoreboardCheckHint",
 *     description="Not a separate documented path merge — FE checks live match via existing GET /api/scoreboard?game_id={match_id} (or GET /api/practice-scoreboard for practice). Live when HTTP 200 and data.is_start===true and data.action!=='EndMatch'. 204/empty = not live."
 * )
 */
final class ConfigurePlayersApiDoc
{
}
