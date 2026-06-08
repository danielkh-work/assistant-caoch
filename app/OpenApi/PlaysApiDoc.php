<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Plays",
 *     description="Offensive play create, update, and listing (H-mark images). Includes configured game play lists, system-suggested plays for scoreboard/match flow, filtered by down and expected yardage."
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
 *     type="object",
 *     description="Configured offensive play. Only `image` is returned for the diagram (from `h_mark_position`); hmark_left/center/right are not included in the response.",
 *     @OA\Property(property="id", type="integer", example=191),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="play_name", type="string", example="Brazil"),
 *     @OA\Property(property="play_type", type="integer", example=1),
 *     @OA\Property(property="zone_selection", type="integer", example=2),
 *     @OA\Property(property="min_expected_yard", type="string", example="short"),
 *     @OA\Property(property="possession", type="string", example="offensive"),
 *     @OA\Property(property="preferred_down", type="string", nullable=true, example="1,2,3"),
 *     @OA\Property(property="strategies", type="string", nullable=true, example="regular"),
 *     @OA\Property(
 *         property="image",
 *         type="string",
 *         nullable=true,
 *         description="Single diagram path from the `h_mark_position` query param (hmark_left, hmark_center, or hmark_right; default hmark_center)",
 *         example="uploads/public/abc.png"
 *     ),
 *     @OA\Property(property="win_result", type="integer", example=3),
 *     @OA\Property(property="loss_result", type="integer", example=1),
 *     @OA\Property(property="practice_win_result", type="integer", example=2),
 *     @OA\Property(property="practice_loss_result", type="integer", example=1),
 *     @OA\Property(property="total_count", type="integer", example=4),
 *     @OA\Property(property="total_practice_count", type="integer", example=2),
 *     @OA\Property(property="win_result_rain", type="integer", example=1),
 *     @OA\Property(property="win_result_snow", type="integer", example=0),
 *     @OA\Property(property="total_rain", type="integer", example=2),
 *     @OA\Property(property="total_snow", type="integer", example=1),
 *     @OA\Property(property="yardage_difference", type="number", format="float", nullable=true, example=12.5)
 * )
 *
 * @OA\Schema(
 *     schema="ConfiguredPlayListBaseResponse",
 *     type="object",
 *     description="HTTP status conveys success or error; body is only data and meta.",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ConfiguredOffensivePlay")
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="total", type="integer", example=12),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=9),
 *         @OA\Property(property="last_page", type="integer", example=3)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ConfiguredPlaySortField",
 *     type="string",
 *     enum={"play_success_rate", "practice_success_rate", "rain_success_rate", "snow_success_rate", "total_score"},
 *     description="Sort field keys for configured-play-list. Rate fields divide win counts by attempt counts (zero attempts = 0). total_score is RPP-based (offensive only; defensive sorts as 0)."
 * )
 *
 * @OA\Get(
 *     path="/api/configured-play-list",
 *     operationId="listConfiguredPlays",
 *     tags={"Plays"},
 *     summary="List configured plays for a game",
 *     description="Returns only plays configured for the authenticated user, league, and match. Pass `possession=offensive` or `possession=defensive`. Optional filters: down, expectedyard, search. Multi-sort via `sort` (comma-separated `field:direction` pairs, applied left-to-right). Rate sorts use SQL ratios; `total_score` uses RPP matchup scoring (meaningful for offensive plays). Plays with zero attempts sort as 0% for rate fields. Offensive: pass `h_mark_position` (`hmark_left`, `hmark_center`, `hmark_right`; default `hmark_center`) — the response includes only `image` for that selection, not the other hmark columns. Defensive: uses the play `image` column.",
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
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         required=false,
 *         description="Multi-column sort. Comma-separated clauses `field:direction` applied left-to-right. Allowed fields: play_success_rate, practice_success_rate, rain_success_rate, snow_success_rate, total_score. Directions: asc, desc. Max 5 clauses. Default (omitted): win_result desc.",
 *         @OA\Schema(
 *             type="string",
 *             example="play_success_rate:asc,practice_success_rate:asc",
 *             pattern="^([a-z_]+:(asc|desc))(,[a-z_]+:(asc|desc))*$"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Configured play list",
 *         @OA\JsonContent(
 *             ref="#/components/schemas/ConfiguredPlayListBaseResponse",
 *             @OA\Examples(
 *                 example="offensive_hmark_left",
 *                 summary="Offensive with h_mark_position=hmark_left",
 *                 value={
 *                     "data": {
 *                         {
 *                             "id": 191,
 *                             "play_name": "Brazil",
 *                             "image": "uploads/public/left-only.png",
 *                             "min_expected_yard": "short",
 *                             "possession": "offensive",
 *                             "win_result": 3
 *                         }
 *                     },
 *                     "meta": {
 *                         "total": 1,
 *                         "current_page": 1,
 *                         "per_page": 9,
 *                         "last_page": 1
 *                     }
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Schema(
 *     schema="SuggestedPlayBenchPlayer",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, example=12),
 *     @OA\Property(property="name", type="string", nullable=true, example="John Doe"),
 *     @OA\Property(property="number", type="string", nullable=true, example="22"),
 *     @OA\Property(property="position_value", type="string", nullable=true, example="Wide receiver W"),
 *     @OA\Property(property="position", type="string", nullable=true, example="WR"),
 *     @OA\Property(property="rpp", type="number", format="float", nullable=true, example=8),
 *     @OA\Property(property="ofp", type="number", format="float", nullable=true, example=85),
 *     @OA\Property(property="speed", type="number", format="float", nullable=true, example=88),
 *     @OA\Property(property="strength", type="number", format="float", nullable=true, example=82)
 * )
 *
 * @OA\Schema(
 *     schema="SuggestedPlayMatchup",
 *     type="object",
 *     @OA\Property(property="offensive_position", type="string", example="Wide receiver W"),
 *     @OA\Property(property="defensive_position", type="string", example="Cornerback"),
 *     @OA\Property(property="strength", type="number", format="float", example=75),
 *     @OA\Property(property="offensive_rpp", type="number", format="float", example=16),
 *     @OA\Property(property="defensive_rpp", type="number", format="float", example=12),
 *     @OA\Property(property="rpp_difference", type="number", format="float", example=4),
 *     @OA\Property(property="strength_percentage", type="number", format="float", example=0.75),
 *     @OA\Property(property="rpp_difference_percentage", type="number", format="float", example=3),
 *     @OA\Property(
 *         property="offensive_players",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SuggestedPlayBenchPlayer")
 *     ),
 *     @OA\Property(
 *         property="defensive_players",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SuggestedPlayBenchPlayer")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuggestedOffensivePlay",
 *     type="object",
 *     description="Offensive play enriched with RPP scoring, matchup breakdown, and result counts for the current match context.",
 *     @OA\Property(property="id", type="integer", example=191),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="play_name", type="string", example="Brazil"),
 *     @OA\Property(property="play_type", type="integer", example=1),
 *     @OA\Property(
 *         property="image",
 *         type="string",
 *         nullable=true,
 *         example="uploads/public/918d00bc211bb556a7dcb320952f3a0a.png",
 *         description="Play diagram path from `h_mark_position` query param (default hmark_center). Only `image` is returned; hmark_left/center/right are not in the response."
 *     ),
 *     @OA\Property(property="zone_selection", type="integer", example=2),
 *     @OA\Property(property="min_expected_yard", type="string", example="45"),
 *     @OA\Property(property="possession", type="string", enum={"offensive", "defensive"}, example="offensive"),
 *     @OA\Property(property="preferred_down", type="string", nullable=true, example="1,2"),
 *     @OA\Property(property="strategies", type="string", nullable=true, example="regular"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="read_1", type="string", nullable=true),
 *     @OA\Property(property="read_2", type="string", nullable=true),
 *     @OA\Property(property="total_score", type="number", format="float", example=12.45, description="RPP-based score used to rank suggestions"),
 *     @OA\Property(property="win_result", type="integer", example=3, description="Play-mode wins (is_practice=0)"),
 *     @OA\Property(property="loss_result", type="integer", example=1),
 *     @OA\Property(property="practice_win_result", type="integer", example=2, description="Practice-mode wins (is_practice=1)"),
 *     @OA\Property(property="practice_loss_result", type="integer", example=0),
 *     @OA\Property(property="total_count", type="integer", example=4),
 *     @OA\Property(property="total_practice_count", type="integer", example=2),
 *     @OA\Property(property="win_result_rain", type="integer", example=1),
 *     @OA\Property(property="win_result_snow", type="integer", example=0),
 *     @OA\Property(property="total_rain", type="integer", example=1),
 *     @OA\Property(property="total_snow", type="integer", example=0),
 *     @OA\Property(property="yardage_difference", type="number", format="float", nullable=true, example=8.5),
 *     @OA\Property(
 *         property="matchups",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SuggestedPlayMatchup")
 *     ),
 *     @OA\Property(
 *         property="rpp_percentage_sum_by_offense",
 *         type="object",
 *         additionalProperties={"type": "number", "format": "float"},
 *         example={"Wide receiver W": 3.2}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuggestedOffensivePlaysResponse",
 *     type="object",
 *     description="Returned when possession is offensive (default). Up to 3 plays per bucket.",
 *     @OA\Property(
 *         property="top_by_score",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SuggestedOffensivePlay")
 *     ),
 *     @OA\Property(
 *         property="top_by_success",
 *         type="array",
 *         description="Ranked by historical win rate; practice uses practice_win_result when is_practice=true",
 *         @OA\Items(ref="#/components/schemas/SuggestedOffensivePlay")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuggestedDefensivePlay",
 *     type="object",
 *     description="Defensive play suggestion returned when possession=defensive.",
 *     @OA\Property(property="id", type="integer", example=55),
 *     @OA\Property(property="league_id", type="integer", example=22),
 *     @OA\Property(property="name", type="string", example="Cover 2"),
 *     @OA\Property(property="image", type="string", nullable=true, example="uploads/public/def.png"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="coverage_type", type="string", nullable=true, example="zone"),
 *     @OA\Property(property="coverage_category", type="string", nullable=true),
 *     @OA\Property(property="preferred_down", type="string", nullable=true, example="1,2,3"),
 *     @OA\Property(property="strategies", type="string", nullable=true),
 *     @OA\Property(property="min_expected_yard", type="string", nullable=true),
 *     @OA\Property(property="opponent_personnel_grouping", type="integer", nullable=true, example=3),
 *     @OA\Property(property="win_result", type="integer", example=2),
 *     @OA\Property(property="practice_win_result", type="integer", example=1),
 *     @OA\Property(property="loss_result", type="integer", example=0),
 *     @OA\Property(property="total_count", type="integer", example=3),
 *     @OA\Property(property="total_practice_count", type="integer", example=1),
 *     @OA\Property(property="yardage_difference", type="number", format="float", nullable=true, example=4.2),
 *     @OA\Property(
 *         property="personals",
 *         type="array",
 *         @OA\Items(type="object")
 *     ),
 *     @OA\Property(property="formation", type="object", nullable=true),
 *     @OA\Property(property="strategyBlitz", type="object", nullable=true)
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/get-suggested-plays",
 *     operationId="getSuggestedPlays",
 *     tags={"Plays"},
 *     summary="Get system-suggested plays for a match",
 *     description="Used from practice/play scoreboard **System Suggestion** flow (PlaySuggestion component). Returns raw `Play` / `DefensivePlay` JSON (not wrapped in status/message). Offensive: `{ top_by_score, top_by_success }`. Pass `h_mark_position` (`hmark_left`, `hmark_center`, `hmark_right`; default `hmark_center`) to set `image` from that H-mark column; other hmark fields are omitted. Defensive: uses play `image` column.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League id (same as route param `id` in /practice-mode/{id}/...)",
 *         @OA\Schema(type="integer", example=22)
 *     ),
 *     @OA\Parameter(name="league_id", in="query", required=false, description="Duplicate of path league id; sent by frontend", @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="match_id", in="query", required=true, description="Game/match id (route gameId, e.g. 36)", @OA\Schema(type="integer", example=36)),
 *     @OA\Parameter(
 *         name="possession",
 *         in="query",
 *         required=true,
 *         description="Determines offensive vs defensive handler",
 *         @OA\Schema(type="string", enum={"offensive", "defensive"}, example="offensive")
 *     ),
 *     @OA\Parameter(name="is_practice", in="query", required=false, description="true/1 for practice mode; false/0 for play mode", @OA\Schema(type="boolean", example=true)),
 *     @OA\Parameter(name="h_mark_position", in="query", required=false, description="Offensive only: H-mark column mapped to response `image` (same as configured-play-list)", @OA\Schema(type="string", enum={"hmark_left", "hmark_center", "hmark_right"}, default="hmark_center", example="hmark_center")),
 *     @OA\Parameter(name="down", in="query", required=false, description="Current down (1-4); maps to preferred_down filter", @OA\Schema(type="integer", enum={1, 2, 3, 4}, example=2)),
 *     @OA\Parameter(name="strategy", in="query", required=false, description="Scoreboard strategy value; maps to strategies column (FIND_IN_SET)", @OA\Schema(type="string", example="regular")),
 *     @OA\Parameter(name="expectedyard", in="query", required=false, description="Expected yard gain from scoreboard; maps to min_expected_yard", @OA\Schema(type="string", example="45")),
 *     @OA\Parameter(name="pkg", in="query", required=false, description="Opponent team package id (defensive suggestions)", @OA\Schema(type="integer", example=5)),
 *     @OA\Parameter(name="zone", in="query", required=false, description="Legacy zone filter; sent by frontend but not applied in current offensive handler", @OA\Schema(type="integer", example=2)),
 *     @OA\Parameter(name="quarter", in="query", required=false, description="Sent by frontend; not applied in current offensive handler", @OA\Schema(type="string", example="1st quarter")),
 *     @OA\Parameter(
 *         name="player",
 *         in="query",
 *         required=false,
 *         description="Selected offensive players from scoreboard (serialized as query params by the Vue client)",
 *         @OA\Schema(type="array", @OA\Items(type="object", @OA\Property(property="value", type="integer"), @OA\Property(property="text", type="string")))
 *     ),
 *     @OA\Parameter(name="opponent_personal_group", in="query", required=false, @OA\Schema(type="integer", example=3)),
 *     @OA\Response(
 *         response=200,
 *         description="Suggested plays. Shape depends on possession.",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(ref="#/components/schemas/SuggestedOffensivePlaysResponse"),
 *                 @OA\Schema(
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/SuggestedDefensivePlay")
 *                 )
 *             },
 *             @OA\Examples(
 *                 example="offensive",
 *                 summary="Offensive possession",
 *                 value={
 *                     "top_by_score": {},
 *                     "top_by_success": {
 *                         {
 *                             "id": 280,
 *                             "play_name": "IN 25(4)-RAM ALL-CHECK",
 *                             "image": "uploads/public/918d00bc211bb556a7dcb320952f3a0a.png",
 *                             "min_expected_yard": "short",
 *                             "possession": "offensive",
 *                             "strategies": "regular",
 *                             "win_result": 0,
 *                             "practice_win_result": 3,
 *                             "total_practice_count": 7,
 *                             "total_score": 0,
 *                             "matchups": {}
 *                         }
 *                     }
 *                 }
 *             ),
 *             @OA\Examples(
 *                 example="defensive",
 *                 summary="Defensive possession",
 *                 value={
 *                     {
 *                         "id": 55,
 *                         "name": "Cover 2",
 *                         "image": "uploads/public/defense.png",
 *                         "win_result": 2,
 *                         "practice_win_result": 1
 *                     }
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
final class PlaysApiDoc
{
}
