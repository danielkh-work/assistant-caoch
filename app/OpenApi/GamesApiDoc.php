<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Games",
 *     description="Game create, listing, details, and duplication APIs."
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
 *     schema="GameBaseResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Game duplicated successfully."),
 *     @OA\Property(property="data", ref="#/components/schemas/Game")
 * )
 *
 * @OA\Post(
 *     path="/api/games/{id}/duplicate",
 *     operationId="duplicateGame",
 *     tags={"Games"},
 *     summary="Duplicate a game and its setup",
 *     description="Creates a new game from the source game and copies match setup rows: configured players, configured offensive plays, configured defensive plays, assigned offense/defense players, personal groups with play pivots, and opponent packages with package players. Runtime fields (`status`, `match_start_date`, `match_end_date`) are cleared on the duplicate. Event/runtime history is not copied: play logs, play results, penalties, and websocket scoreboards stay only on the source game.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Source game id to duplicate",
 *         @OA\Schema(type="integer", example=36)
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
