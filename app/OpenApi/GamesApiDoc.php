<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Games",
 *     description="Game create, listing, details, and duplication APIs."
 * )
 *
 * @OA\Schema(
 *     schema="OpponentTeamOption",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=216),
 *     @OA\Property(property="team_name", type="string", example="CNDF Notre Dame")
 * )
 *
 * @OA\Schema(
 *     schema="OpponentTeamOptionListResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Opponent teams list"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OpponentTeamOption")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Game",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=36),
 *     @OA\Property(property="type", type="integer", nullable=true, description="1 = regular game, 2 = practice game", example=2),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="creator_id", type="integer", example=1818),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="descrptions", type="string", nullable=true),
 *     @OA\Property(property="my_team_id", type="integer", example=217),
 *     @OA\Property(property="oponent_team_id", type="integer", example=216),
 *     @OA\Property(property="date", type="string", nullable=true, example="2026-06-26 14:34:00"),
 *     @OA\Property(property="location", type="string", nullable=true, example="america,usa"),
 *     @OA\Property(property="location_type", type="string", nullable=true, example="home"),
 *     @OA\Property(property="neutral_location", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", nullable=true, example=null),
 *     @OA\Property(property="match_start_date", type="string", nullable=true, example=null),
 *     @OA\Property(property="match_end_date", type="string", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="DuplicateGameRequest",
 *     type="object",
 *     @OA\Property(
 *         property="date",
 *         type="string",
 *         nullable=true,
 *         description="Optional new game date for the duplicated game.",
 *         example="2026-08-20 19:30:00"
 *     ),
 *     @OA\Property(
 *         property="opponent_team_id",
 *         type="integer",
 *         nullable=true,
 *         description="Optional replacement opponent team id. Must belong to the same league and cannot be a practice team.",
 *         example=216
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GameBaseResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Game duplicated successfully."),
 *     @OA\Property(property="data", ref="#/components/schemas/Game")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{leagueId}/opponent-teams",
 *     operationId="listOpponentTeamsForLeague",
 *     tags={"Games"},
 *     summary="List opponent teams for a league",
 *     description="Returns up to the first 300 non-practice league teams as lightweight opponent options. Practice teams (`is_practice = 1`) are excluded. Use `search` to filter by team name. Pass `my_team_id` to exclude the current my team from opponent options.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="leagueId",
 *         in="path",
 *         required=true,
 *         description="League id",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\Parameter(
 *         name="my_team_id",
 *         in="query",
 *         required=false,
 *         description="Current my-team id to exclude from opponent options",
 *         @OA\Schema(type="integer", example=217)
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=false,
 *         description="Optional team name search",
 *         @OA\Schema(type="string", example="CNDF")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Opponent team options",
 *         @OA\JsonContent(ref="#/components/schemas/OpponentTeamOptionListResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Post(
 *     path="/api/games/{id}/duplicate",
 *     operationId="duplicateGame",
 *     tags={"Games"},
 *     summary="Duplicate a game and its setup",
 *     description="Creates a new game from the source game. Optional `date` updates the duplicated game date. If `opponent_team_id` is omitted or unchanged, all existing setup is copied. If `opponent_team_id` is changed, the duplicate uses the new opponent team, preserves my-team setup, and skips original-opponent setup such as opponent configured players, opponent groups, defensive configured plays, and opponent packages. Runtime fields (`status`, `match_start_date`, `match_end_date`) are cleared on the duplicate. Event/runtime history is not copied: play logs, play results, penalties, and websocket scoreboards stay only on the source game.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Source game id to duplicate",
 *         @OA\Schema(type="integer", example=36)
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(ref="#/components/schemas/DuplicateGameRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Game duplicated",
 *         @OA\JsonContent(ref="#/components/schemas/GameBaseResponse")
 *     ),
 *     @OA\Response(response=400, description="Duplicate failed"),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=404, description="Game not found")
 * )
 */
final class GamesApiDoc
{
}
