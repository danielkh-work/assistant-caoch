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
 *     schema="UpcomingLeagueMatch",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=36),
 *     @OA\Property(property="date", type="string", nullable=true, example="2026-08-20 19:30:00"),
 *     @OA\Property(property="status", type="string", nullable=true, example="scheduled"),
 *     @OA\Property(property="match_start_date", type="string", nullable=true, example=null),
 *     @OA\Property(property="match_end_date", type="string", nullable=true, example=null),
 *     @OA\Property(property="my_team_id", type="integer", example=217),
 *     @OA\Property(property="my_team_name", type="string", nullable=true, example="Giants St-Jean-sur-Le-Richelieu"),
 *     @OA\Property(property="opponent_team_id", type="integer", example=216),
 *     @OA\Property(property="opponent_team_name", type="string", nullable=true, example="CNDF Notre Dame"),
 *     @OA\Property(property="location", type="string", nullable=true, example="Home Stadium"),
 *     @OA\Property(property="location_type", type="string", nullable=true, example="home"),
 *     @OA\Property(property="neutral_location", type="string", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="UpcomingLeagueMatchesResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Upcoming real matches list"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="league_id", type="integer", example=22),
 *         @OA\Property(property="league_name", type="string", example="QFA League"),
 *         @OA\Property(property="matches_count", type="integer", example=2),
 *         @OA\Property(
 *             property="matches",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/UpcomingLeagueMatch")
 *         )
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
 * @OA\Schema(
 *     schema="GamesByLeaguePagination",
 *     type="object",
 *     @OA\Property(property="total", type="integer", example=25),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=18),
 *     @OA\Property(property="last_page", type="integer", example=3)
 * )
 *
 * @OA\Schema(
 *     schema="GamesByLeagueListResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="games list"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Game")
 *     ),
 *     @OA\Property(property="pagination", ref="#/components/schemas/GamesByLeaguePagination")
 * )
 *
 * @OA\Get(
 *     path="/api/games/league/{leagueId}",
 *     operationId="getGamesByLeague",
 *     tags={"Games"},
 *     summary="List games for a league with sort, pagination, and filters",
 *     description="Returns games for the given league. Without a date filter, the default feed returns upcoming games first (6–9 on page 1, then up to 9 per page) followed by ended games (3–6 on page 1, then up to 6 per page; most recent ended first). When `start_date`, `end_date`, or `date` is supplied, standard pagination applies with upcoming before ended within the filtered range. Supports optional `type` and `status` filters.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="leagueId",
 *         in="path",
 *         required=true,
 *         description="League id",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\Parameter(
 *         name="type",
 *         in="query",
 *         required=false,
 *         description="Optional game type filter. `1` = regular game, `2` = practice game.",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         required=false,
 *         description="Optional status filter. Use `not-ended` to exclude games with status `ended`.",
 *         @OA\Schema(type="string", example="not-ended")
 *     ),
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         required=false,
 *         description="Optional inclusive range start on `games.date` (`YYYY-MM-DD`). Can be used alone or with `end_date`.",
 *         @OA\Schema(type="string", format="date", example="2026-09-01")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         required=false,
 *         description="Optional inclusive range end on `games.date` (`YYYY-MM-DD`). Can be used alone or with `start_date`.",
 *         @OA\Schema(type="string", format="date", example="2026-09-30")
 *     ),
 *     @OA\Parameter(
 *         name="date",
 *         in="query",
 *         required=false,
 *         description="Optional single-day filter on `games.date` (`YYYY-MM-DD`). Equivalent to setting both `start_date` and `end_date` to the same value.",
 *         @OA\Schema(type="string", format="date", example="2026-09-15")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         required=false,
 *         description="Page number. Defaults to 1.",
 *         @OA\Schema(type="integer", minimum=1, example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Games per page. Defaults to 18 (6 rows of 3), maximum 100.",
 *         @OA\Schema(type="integer", minimum=1, maximum=100, example=18)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Paginated games list",
 *         @OA\JsonContent(ref="#/components/schemas/GamesByLeagueListResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues-upcoming-matches",
 *     operationId="leaguesUpcomingMatches",
 *     tags={"Games"},
 *     summary="Get all user leagues with their upcoming matches",
 *     description="Returns all leagues visible to the authenticated user with their upcoming matches. For each league, currently returns the closest upcoming match in an array (`upcoming_matches`) so multiple matches can be returned later. Leagues are sorted by their soonest upcoming match date. Practice matches (`games.type = 2`), started/ended matches, and matches whose `date` datetime is already in the past are excluded. Only never-played games (`status`, `match_start_date`, and `match_end_date` all null) are included.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="User leagues with upcoming matches",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="User leagues with upcoming matches"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="league_id", type="integer", example=22),
 *                     @OA\Property(property="league_name", type="string", example="QFA League"),
 *                     @OA\Property(
 *                         property="upcoming_matches",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=36),
 *                             @OA\Property(property="date", type="string", nullable=true, example="2026-08-20 19:30:00"),
 *                             @OA\Property(property="status", type="string", nullable=true, example=null),
 *                             @OA\Property(property="match_start_date", type="string", nullable=true, example=null),
 *                             @OA\Property(property="match_end_date", type="string", nullable=true, example=null),
 *                             @OA\Property(property="my_team_id", type="integer", example=217),
 *                             @OA\Property(property="my_team_name", type="string", nullable=true, example="Giants St-Jean-sur-Le-Richelieu"),
 *                             @OA\Property(property="opponent_team_id", type="integer", example=216),
 *                             @OA\Property(property="opponent_team_name", type="string", nullable=true, example="CNDF Notre Dame"),
 *                             @OA\Property(property="location", type="string", nullable=true, example="Home Stadium"),
 *                             @OA\Property(property="location_type", type="string", nullable=true, example="home"),
 *                             @OA\Property(property="neutral_location", type="string", nullable=true, example=null)
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{leagueId}/opponent-teams",
 *     operationId="listOpponentTeamsForLeague",
 *     tags={"Games"},
 *     summary="List opponent teams for a league",
 *     description="Returns up to the first 300 non-practice league teams as lightweight opponent options. Practice teams (`is_practice = 1`) are excluded. Use `search` to filter by team name. The API excludes the league's non-practice `type = 1` team as my team.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="leagueId",
 *         in="path",
 *         required=true,
 *         description="League id",
 *         @OA\Schema(type="integer", example=22)
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
