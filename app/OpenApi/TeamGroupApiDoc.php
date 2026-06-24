<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Team Groups",
 *     description="Team-level group management"
 * )
 *
 * @OA\Schema(
 *     schema="TeamGroup",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="team_id", type="integer", example=216),
 *     @OA\Property(property="name", type="string", example="Pressure Front"),
 *     @OA\Property(property="group_name", type="string", example="Pressure Front", description="Legacy alias for `name`"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Third-down pressure and stunt package"),
 *     @OA\Property(property="type", type="string", enum={"offense", "defense"}, example="defense"),
 *     @OA\Property(property="segment", type="integer", enum={7,11,12}, example=11, description="Legacy alias: `group_level`"),
 *     @OA\Property(property="group_level", type="integer", nullable=true, example=11, description="Legacy alias for `segment`"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "draft"}, example="active"),
 *     @OA\Property(property="is_playing", type="boolean", example=true, description="True when the group is included in the team's active configuration"),
 *     @OA\Property(property="players", type="array", @OA\Items(type="object"))
 * )
 *
 * @OA\Schema(
 *     schema="TeamPlayerRosterItem",
 *     type="object",
 *     @OA\Property(property="player_id", type="integer", example=88),
 *     @OA\Property(property="name", type="string", example="Will Johnson"),
 *     @OA\Property(property="player", type="string", example="Will Johnson"),
 *     @OA\Property(property="target", type="string", example="WR"),
 *     @OA\Property(property="number", type="string", example="12"),
 *     @OA\Property(property="strength", type="integer", example=80),
 *     @OA\Property(property="height", type="string", example="6-1"),
 *     @OA\Property(property="weight", type="string", example="210"),
 *     @OA\Property(property="dob_raw", type="string", nullable=true, example="2001-04-20"),
 *     @OA\Property(property="speed", type="integer", example=92),
 *     @OA\Property(property="positions", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="ofp", type="string", example="N/A"),
 *     @OA\Property(property="size", type="integer", example=0),
 *     @OA\Property(property="is_playing", type="boolean", example=true, description="True when the player belongs to a configured playing group"),
 *     @OA\Property(property="fromDatabase", type="boolean", example=true),
 *     @OA\Property(property="position", type="string", example="N/A")
 * )
 *
 * @OA\Schema(
 *     schema="TeamGroupWriteRequest",
 *     type="object",
 *     required={"name", "type", "segment"},
 *     @OA\Property(property="team_id", type="integer", nullable=true, example=216, description="Optional consistency check; should match the route team"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Pressure Front"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Third-down pressure and stunt package"),
 *     @OA\Property(property="type", type="string", enum={"offense", "defense"}, example="defense"),
 *     @OA\Property(property="segment", type="integer", enum={7,11,12}, example=11),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "draft"}, example="draft"),
 *     @OA\Property(property="player_ids", type="array", @OA\Items(type="integer", example=88), description="Team player IDs to attach to the group")
 * )
 *
 * @OA\Schema(
 *     schema="TeamGroupConfigurationSyncRequest",
 *     type="object",
 *     required={"group_ids"},
 *     @OA\Property(
 *         property="group_ids",
 *         type="array",
 *         description="Full replacement list of group IDs to keep configured for the team. Any previously configured group not included here will be removed.",
 *         @OA\Items(type="integer", example=12)
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/teams/{team}/groups",
 *     tags={"Team Groups"},
 *     summary="List team groups",
 *     description="Returns team-scoped groups. Supported filters: `name`, `search`, `status`, `type`, and `segment`. The team is resolved from the route `/teams/{team}`.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string", example="pressure")),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string", example="pressure")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active", "inactive", "draft"})),
 *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"offense", "defense"})),
 *     @OA\Parameter(name="segment", in="query", required=false, @OA\Schema(type="integer", enum={7,11,12})),
 *     @OA\Response(response=200, description="Team groups listed", @OA\JsonContent(type="object", @OA\Property(property="status", type="integer", example=200), @OA\Property(property="message", type="string", example="Team groups fetched successfully"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TeamGroup")))),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     security={{"sanctum":{}}}
 * )
 *
 * @OA\Post(
 *     path="/api/teams/{team}/groups",
 *     tags={"Team Groups"},
 *     summary="Create team group",
 *     description="Creates a persistent group under a team and attaches the provided team player IDs through the pivot table.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TeamGroupWriteRequest")),
 *     @OA\Response(response=200, description="Team group created", @OA\JsonContent(ref="#/components/schemas/TeamGroup")),
 *     @OA\Response(response=422, description="Validation error"),
 *     security={{"sanctum":{}}}
 * )
 *
 * @OA\Put(
 *     path="/api/teams/{team}/groups/{group}",
 *     tags={"Team Groups"},
 *     summary="Update team group",
 *     description="Updates the group attributes and synchronizes attached players through the pivot table. The `team` segment must match the owning team of the group.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer", example=12)),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TeamGroupWriteRequest")),
 *     @OA\Response(response=200, description="Team group updated", @OA\JsonContent(ref="#/components/schemas/TeamGroup")),
 *     @OA\Response(response=422, description="Validation error"),
 *     security={{"sanctum":{}}}
 * )
 *
 * @OA\Delete(
 *     path="/api/teams/{team}/groups/{group}",
 *     tags={"Team Groups"},
 *     summary="Delete team group",
 *     description="Deletes a team-scoped group. The `team` segment must match the owning team of the group.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer", example=12)),
 *     @OA\Response(response=200, description="Team group deleted successfully"),
 *     @OA\Response(response=422, description="Group not found"),
 *     security={{"sanctum":{}}}
 * )
 *
 * @OA\Put(
 *     path="/api/teams/{team}/group-config",
 *     tags={"Team Groups"},
 *     summary="Sync team group configuration",
 *     description="Replace-all sync endpoint for a team's configured groups. Submitted group IDs are added, and any previously configured groups missing from the request are removed. Validation rejects groups that do not belong to the team, are not active, or do not have a player count matching the group segment.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TeamGroupConfigurationSyncRequest")),
 *     @OA\Response(response=200, description="Team group configuration synced successfully"),
 *     @OA\Response(response=422, description="Validation error"),
 *     security={{"sanctum":{}}}
 * )
 *
 * @OA\Get(
 *     path="/api/teams/{team}/players",
 *     tags={"Team Groups"},
 *     summary="List team roster for grouping",
 *     description="Returns the team player roster used by the team-group editor.",
 *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer", example=216)),
 *     @OA\Response(response=200, description="Team players listed", @OA\JsonContent(type="object", @OA\Property(property="status", type="integer", example=200), @OA\Property(property="message", type="string", example="Team players fetched successfully"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TeamPlayerRosterItem")))),
 *     security={{"sanctum":{}}}
 * )
 */
final class TeamGroupApiDoc
{
}
