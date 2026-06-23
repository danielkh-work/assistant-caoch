<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Team Game Group Configuration",
 *     description="Manage selected player group configurations for a specific team within a game. Allows saving and retrieving which groups are selected for a team-game combination."
 * )
 *
 * @OA\Schema(
 *     schema="TeamGameGroupConfiguration",
 *     type="object",
 *     description="Selected group configuration for a team within a game",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="game_id", type="integer", example=36),
 *     @OA\Property(property="team_id", type="integer", example=22),
 *     @OA\Property(property="group_id", type="integer", example=216),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-06-24T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-06-24T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="TeamGameGroupConfigurationResponse",
 *     type="object",
 *     description="Response containing selected group IDs for a team-game combination",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Selected groups retrieved successfully"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="game_id", type="integer", example=36),
 *         @OA\Property(property="team_id", type="integer", example=22),
 *         @OA\Property(
 *             property="selected_group_ids",
 *             type="array",
 *             @OA\Items(type="integer"),
 *             example={216, 217, 218}
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateTeamGameGroupConfigurationRequest",
 *     type="object",
 *     required={"game_id", "team_id", "group_ids"},
 *     @OA\Property(property="game_id", type="integer", example=36, description="The game ID"),
 *     @OA\Property(property="team_id", type="integer", example=22, description="The team ID"),
 *     @OA\Property(
 *         property="group_ids",
 *         type="array",
 *         description="Array of group IDs to select for this team-game combination",
 *         @OA\Items(type="integer"),
 *         example={216, 217, 218}
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/team-game-group-configurations",
 *     operationId="getTeamGameGroupConfigurations",
 *     tags={"Team Game Group Configuration"},
 *     summary="Get selected groups for a game and team",
 *     description="Retrieve all group selections currently associated with the specified game_id and team_id. Returns an array of selected group IDs.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="game_id",
 *         in="query",
 *         required=true,
 *         description="The game ID",
 *         @OA\Schema(type="integer", example=36)
 *     ),
 *     @OA\Parameter(
 *         name="team_id",
 *         in="query",
 *         required=true,
 *         description="The team ID",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Selected groups retrieved successfully",
 *         @OA\JsonContent(ref="#/components/schemas/TeamGameGroupConfigurationResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/team-game-group-configurations",
 *     operationId="updateTeamGameGroupConfigurations",
 *     tags={"Team Game Group Configuration"},
 *     summary="Update selected groups for a game and team",
 *     description="Persist the user's current group selection for a specific team and game. Existing records are replaced with the new selection. All group IDs must belong to the specified game and team.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/UpdateTeamGameGroupConfigurationRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Selected groups updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/TeamGameGroupConfigurationResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error - invalid game_id, team_id, or group_ids format"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request - one or more group IDs are invalid or do not belong to this game and team"
 *     )
 * )
 */
final class TeamGameGroupConfigurationApiDoc
{
}
