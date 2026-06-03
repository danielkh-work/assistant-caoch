<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Plays",
 *     description="Offensive play create, update, and listing (H-mark images). Includes configured game play lists filtered by down and expected yardage."
 * )
 *
 * @OA\Schema(
 *     schema="Play",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=191),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="play_name", type="string", example="Brazil"),
 *     @OA\Property(property="play_type", type="integer", example=1),
 *     @OA\Property(property="offensive_play_type", type="string", enum={"run", "pass", "rpo", "play_action"}, example="pass"),
 *     @OA\Property(property="zone_selection", type="integer", example=2),
 *     @OA\Property(property="min_expected_yard", type="string", example="short"),
 *     @OA\Property(property="max_expected_yard", type="string", example="1"),
 *     @OA\Property(property="possession", type="string", enum={"offensive", "defensive"}, example="offensive"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="hmark_left", type="string", nullable=true, example="uploads/public/abc.png"),
 *     @OA\Property(property="hmark_center", type="string", nullable=true, example="uploads/public/def.png"),
 *     @OA\Property(property="hmark_right", type="string", nullable=true, example="uploads/public/ghi.png"),
 *     @OA\Property(property="preferred_down", type="string", nullable=true, example="1,2,3"),
 *     @OA\Property(property="strategies", type="string", nullable=true, example="regular,hurry up"),
 *     @OA\Property(property="read_1", type="string", nullable=true),
 *     @OA\Property(property="read_2", type="string", nullable=true),
 *     @OA\Property(property="win_result", type="integer", description="Present on list responses", example=3),
 *     @OA\Property(property="loss_result", type="integer", example=1),
 *     @OA\Property(property="yardage_difference", type="number", format="float", nullable=true, example=12.5)
 * )
 *
 * @OA\Schema(
 *     schema="PlayBaseResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Play Uploaded Successfully"),
 *     @OA\Property(property="data", ref="#/components/schemas/Play")
 * )
 *
 * @OA\Schema(
 *     schema="PlayListBaseResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Play Uploaded List "),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Play")
 *     ),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="total", type="integer", example=12),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=4),
 *         @OA\Property(property="last_page", type="integer", example=3)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PlayMultipartFields",
 *     type="object",
 *     required={
 *         "play_name", "playType", "league_id", "play_type", "zone_selection",
 *         "min_expected_yard", "max_expected_yard", "target_offensive", "opposing_defensive",
 *         "pre_snap_motion", "play_action_fake", "possession"
 *     },
 *     @OA\Property(property="play_name", type="string", example="Brazil"),
 *     @OA\Property(property="playType", type="string", enum={"run", "pass", "rpo", "play_action"}, example="pass"),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="play_type", type="integer", example=1),
 *     @OA\Property(property="zone_selection", type="integer", example=2),
 *     @OA\Property(property="min_expected_yard", type="string", example="short"),
 *     @OA\Property(property="max_expected_yard", type="string", example="1"),
 *     @OA\Property(property="target_offensive", type="integer", example=1),
 *     @OA\Property(property="opposing_defensive", type="integer", example=3),
 *     @OA\Property(property="pre_snap_motion", type="integer", example=0),
 *     @OA\Property(property="play_action_fake", type="integer", example=0),
 *     @OA\Property(property="possession", type="string", enum={"offensive", "defensive"}, example="offensive"),
 *     @OA\Property(property="quarter", type="integer", example=1),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="preferred_down", type="string", example="1,2,3"),
 *     @OA\Property(property="strategies", type="string", example="regular,hurry up"),
 *     @OA\Property(property="read_2", type="string", nullable=true, description="Stored as read_1"),
 *     @OA\Property(property="read_3", type="string", nullable=true, description="Stored as read_2"),
 *     @OA\Property(property="position_status", type="integer", example=1),
 *     @OA\Property(property="groups[]", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="offensive[40]", type="integer", example=0, description="Offensive position strength keyed by position id"),
 *     @OA\Property(property="defensive[70]", type="integer", example=0, description="Defensive position strength keyed by position id"),
 *     @OA\Property(property="hmark_left", type="string", format="binary", description="Required on create"),
 *     @OA\Property(property="hmark_center", type="string", format="binary", description="Required on create"),
 *     @OA\Property(property="hmark_right", type="string", format="binary", description="Required on create"),
 *     @OA\Property(property="video", type="string", format="binary", nullable=true)
 * )
 *
 * @OA\Get(
 *     path="/api/upload-play-list",
 *     operationId="listPlays",
 *     tags={"Plays"},
 *     summary="List offensive plays",
 *     description="Returns plays for a league including `hmark_left`, `hmark_center`, and `hmark_right`. Pass `page`, `per_page`, or `search` to enable pagination.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league_id", in="query", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=4)),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string", example="Brazil")),
 *     @OA\Response(response=200, description="Play list", @OA\JsonContent(ref="#/components/schemas/PlayListBaseResponse")),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Post(
 *     path="/api/uplaod-play",
 *     operationId="createPlay",
 *     tags={"Plays"},
 *     summary="Create offensive play",
 *     description="Upload a new offensive play. Requires all three H-mark images (`hmark_left`, `hmark_center`, `hmark_right`). Note: route path uses legacy spelling `uplaod-play`.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/PlayMultipartFields")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Play created", @OA\JsonContent(ref="#/components/schemas/PlayBaseResponse")),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Post(
 *     path="/api/update-play/{id}",
 *     operationId="updatePlay",
 *     tags={"Plays"},
 *     summary="Update offensive play",
 *     description="Update an existing play. H-mark images are optional when existing paths are already stored; send new files to replace. `playType` is optional on update.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=191)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/PlayMultipartFields")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Play updated", @OA\JsonContent(ref="#/components/schemas/PlayBaseResponse")),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Schema(
 *     schema="ConfiguredOffensivePlay",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Play"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="image",
 *                 type="string",
 *                 nullable=true,
 *                 description="Mapped from the selected H-mark column (`h_mark_position` query param)",
 *                 example="uploads/public/abc.png"
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ConfiguredPlayListBaseResponse",
 *     type="object",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Configured play list"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ConfiguredOffensivePlay")
 *     ),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         @OA\Property(property="total", type="integer", example=12),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=9),
 *         @OA\Property(property="last_page", type="integer", example=3)
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/configured-play-list",
 *     operationId="listConfiguredPlays",
 *     tags={"Plays"},
 *     summary="List configured plays for a game",
 *     description="Returns only plays configured for the authenticated user, league, and match. Pass `possession=offensive` or `possession=defensive`. Optional filters: down, expectedyard, search. For offensive plays, maps the selected `h_mark_position` column into response field `image` (default `hmark_center`). Defensive plays use the play `image` column.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league_id", in="query", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="matchId", in="query", required=true, @OA\Schema(type="integer", example=36)),
 *     @OA\Parameter(name="possession", in="query", required=true, description="Which configured plays to return", @OA\Schema(type="string", enum={"offensive", "defensive"}, example="offensive")),
 *     @OA\Parameter(name="down", in="query", required=false, description="Filter by preferred down (1-4)", @OA\Schema(type="integer", enum={1, 2, 3, 4}, example=1)),
 *     @OA\Parameter(name="expectedyard", in="query", required=false, @OA\Schema(type="string", enum={"short", "medium", "long", "open_down"}, example="short")),
 *     @OA\Parameter(name="h_mark_position", in="query", required=false, description="Offensive only: H-mark column used for response `image`", @OA\Schema(type="string", enum={"hmark_left", "hmark_center", "hmark_right"}, default="hmark_center", example="hmark_center")),
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=9, default=9)),
 *     @OA\Parameter(name="search", in="query", required=false, description="Offensive: play_name; defensive: name", @OA\Schema(type="string", example="Brazil")),
 *     @OA\Response(response=200, description="Configured play list", @OA\JsonContent(ref="#/components/schemas/ConfiguredPlayListBaseResponse")),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
final class PlaysApiDoc
{
}
