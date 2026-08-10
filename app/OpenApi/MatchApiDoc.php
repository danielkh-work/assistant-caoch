<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Matches",
 *     description="League match listing and score update APIs."
 * )
 *
 * @OA\Schema(
 *     schema="LeagueMatch",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=36),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="sport_id", type="integer", example=1),
 *     @OA\Property(property="my_team_id", type="integer", example=217),
 *     @OA\Property(property="oponent_team_id", type="integer", example=216),
 *     @OA\Property(property="my_team_score", type="integer", nullable=true, example=14),
 *     @OA\Property(property="oponent_team_score", type="integer", nullable=true, example=7),
 *     @OA\Property(property="my_team_status", type="string", example="WIN"),
 *     @OA\Property(property="opponent_team_status", type="string", example="LOSS"),
 *     @OA\Property(property="summary", type="string", example="Home Team (WIN) vs Opponent Team (LOSS)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="LeagueMatchPagination",
 *     type="object",
 *     @OA\Property(property="total", type="integer", example=25),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="last_page", type="integer", example=3)
 * )
 *
 * @OA\Schema(
 *     schema="LeagueMatchListResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Matches List  "),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/LeagueMatch")
 *     ),
 *     @OA\Property(property="pagination", ref="#/components/schemas/LeagueMatchPagination")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/matches",
 *     operationId="listLeagueMatches",
 *     tags={"Matches"},
 *     summary="List league matches with pagination",
 *     description="Returns authenticated user's matches for the selected league. Results are ordered by newest first and include computed team result statuses and summary text.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League id",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number. Defaults to 1.",
 *         @OA\Schema(type="integer", minimum=1, example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Matches per page. Defaults to 10, maximum 100.",
 *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Paginated match list",
 *         @OA\JsonContent(ref="#/components/schemas/LeagueMatchListResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
final class MatchApiDoc
{
}
