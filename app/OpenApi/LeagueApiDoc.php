<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Leagues",
 *     description="League settings and configuration"
 * )
 *
 * @OA\Put(
 *     path="/api/leagues/{league}",
 *     operationId="updateLeague",
 *     tags={"Leagues"},
 *     summary="Update league",
 *     description="Partial update: only fields included in the request body are changed. Omitted fields are left unchanged. Requires the league to belong to the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League primary key",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", example="Updated League"),
 *             @OA\Property(property="sport_id", type="integer", example=1),
 *             @OA\Property(property="league_rule_id", type="integer", example=1),
 *             @OA\Property(property="location", type="string", nullable=true, example="Toronto"),
 *             @OA\Property(property="number_of_team", type="integer", nullable=true, example=4),
 *             @OA\Property(property="number_of_downs", type="string", nullable=true, example="4"),
 *             @OA\Property(property="length_of_field", type="string", nullable=true, example="100"),
 *             @OA\Property(property="number_of_timeouts", type="integer", nullable=true, example=3),
 *             @OA\Property(property="clock_time", type="string", nullable=true, example="NFL"),
 *             @OA\Property(property="number_of_quarters", type="integer", nullable=true, example=4),
 *             @OA\Property(property="length_of_quarters", type="integer", nullable=true, example=15),
 *             @OA\Property(property="stop_time_reason", type="string", nullable=true),
 *             @OA\Property(property="overtime_rules", type="integer", nullable=true),
 *             @OA\Property(property="number_of_players", type="integer", nullable=true, example=11),
 *             @OA\Property(property="practice_number_players", type="integer", nullable=true, example=7),
 *             @OA\Property(property="warning_time_minutes", type="integer", nullable=true, example=2),
 *             @OA\Property(property="flag_tbd", type="string", nullable=true),
 *             @OA\Property(
 *                 property="rpp_configuration",
 *                 type="string",
 *                 nullable=true,
 *                 enum={"with_rpp", "without_rpp"},
 *                 example="with_rpp"
 *             ),
 *             @OA\Property(
 *                 property="team_name",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", nullable=true, example=10),
 *                     @OA\Property(property="name", type="string", example="Team A")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="League updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="League updated successfully"),
 *             @OA\Property(property="data", type="object", description="Updated league with teams, league_rule, and sport relations")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=404, description="League not found or not owned by the authenticated head coach"),
 *     @OA\Response(response=422, description="Validation error or empty payload")
 * )
 */
final class LeagueApiDoc
{
}
