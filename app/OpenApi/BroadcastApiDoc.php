<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Broadcast",
 *     description="Coach-group Pusher broadcasts between head coach and assistant coach"
 * )
 *
 * @OA\Schema(
 *     schema="YardagePlayToAssistantRequest",
 *     description="Exact JSON keys read by BroadCastScoreController::yardagePlaytoAssistant (Vue client may send `play`; stored in broadcast payload as `play`).",
 *     @OA\Property(property="PlayName", type="string", example="IN 25(4)-RAM ALL-CHECK"),
 *     @OA\Property(property="playYardageGain", type="integer", nullable=true, example=45, description="Current ball position / yardage slider value"),
 *     @OA\Property(property="playSliderDirection", type="string", nullable=true, example="ltr"),
 *     @OA\Property(property="targetTeam", type="string", nullable=true, example="offensive", description="Possession / target team id or type from scoreboard"),
 *     @OA\Property(
 *         property="suggestionData",
 *         type="object",
 *         nullable=true,
 *         description="Scoreboard context when HC opened System Suggestions",
 *         @OA\Property(property="yardage", type="integer", example=10),
 *         @OA\Property(property="positionNumber", type="integer", example=45),
 *         @OA\Property(property="quarter", type="integer", example=1),
 *         @OA\Property(property="down", type="integer", example=2),
 *         @OA\Property(property="target", type="string", example="12"),
 *         @OA\Property(property="myteamId", type="integer", example=1),
 *         @OA\Property(property="oppteamId", type="integer", example=2),
 *         @OA\Property(property="strategies", type="string", example="regular"),
 *         @OA\Property(property="weather_status", type="string", example="Normal"),
 *         @OA\Property(property="weatherSelection", type="string", example="Normal"),
 *         @OA\Property(property="game_id", type="integer", example=36),
 *         @OA\Property(property="league_id", type="integer", example=22),
 *         @OA\Property(property="time", type="string", example="12:00"),
 *         @OA\Property(property="my_points", type="integer", example=7),
 *         @OA\Property(property="oponent_points", type="integer", example=0),
 *         @OA\Property(property="mode", type="string", example="practice")
 *     ),
 *     @OA\Property(property="selectedPlayIds", type="integer", nullable=true, example=280),
 *     @OA\Property(property="playObject", type="object", nullable=true, description="Selected play object (Vue also sends `play` as alias)", @OA\Property(property="id", type="integer", example=280), @OA\Property(property="title", type="string", example="IN 25(4)-RAM ALL-CHECK")),
 *     @OA\Property(property="type", type="string", nullable=true, enum={"offensive", "defensive"}, example="offensive"),
 *     @OA\Property(
 *         property="targetPlayers",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(type="object", @OA\Property(property="value", type="integer"), @OA\Property(property="text", type="string"))
 *     ),
 *     @OA\Property(property="my_team", type="integer", nullable=true, example=1),
 *     @OA\Property(property="opponent_team", type="integer", nullable=true, example=2),
 *     @OA\Property(property="mode", type="string", nullable=true, enum={"practice", "play"}, example="practice")
 * )
 *
 * @OA\Schema(
 *     schema="YardagePlayBroadcastPayload",
 *     @OA\Property(property="playName", type="string", nullable=true, example="IN 25(4)-RAM ALL-CHECK"),
 *     @OA\Property(property="yardageGain", type="integer", nullable=true, example=45),
 *     @OA\Property(property="sliderDirection", type="string", nullable=true, example="ltr"),
 *     @OA\Property(property="targetTeam", type="string", nullable=true, example="offensive"),
 *     @OA\Property(property="suggestionData", type="object", nullable=true),
 *     @OA\Property(property="selectedPlayIds", type="integer", nullable=true, example=280),
 *     @OA\Property(property="play", type="object", nullable=true),
 *     @OA\Property(property="type", type="string", nullable=true, example="offensive"),
 *     @OA\Property(property="targetPlayers", type="array", nullable=true, @OA\Items(type="object")),
 *     @OA\Property(property="my_team", type="integer", nullable=true, example=1),
 *     @OA\Property(property="opponent_team", type="integer", nullable=true, example=2),
 *     @OA\Property(property="mode", type="string", nullable=true, example="practice")
 * )
 *
 * @OA\Schema(
 *     schema="YardagePlayToAssistantResponse",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Yardage play broadcast sent to assistant coach."),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="head_coach_id", type="integer", example=5),
 *         @OA\Property(property="channel", type="string", example="coach-group.5"),
 *         @OA\Property(property="event", type="string", example="assistant.coaches"),
 *         @OA\Property(property="payload", ref="#/components/schemas/YardagePlayBroadcastPayload")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/play/show-yardage/assistant-coach",
 *     operationId="yardagePlayToAssistant",
 *     tags={"Broadcast"},
 *     summary="Head coach → assistant: broadcast play run so AC opens the position-after-play modal",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(ref="#/components/schemas/YardagePlayToAssistantRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Broadcast dispatched to assistant coach",
 *         @OA\JsonContent(
 *             ref="#/components/schemas/YardagePlayToAssistantResponse",
 *             example={
 *                 "status": 200,
 *                 "message": "Yardage play broadcast sent to assistant coach.",
 *                 "data": {
 *                     "head_coach_id": 5,
 *                     "channel": "coach-group.5",
 *                     "event": "assistant.coaches",
 *                     "payload": {
 *                         "playName": "IN 25(4)-RAM ALL-CHECK",
 *                         "yardageGain": 45,
 *                         "sliderDirection": "ltr",
 *                         "targetTeam": "offensive",
 *                         "suggestionData": {
 *                             "yardage": 10,
 *                             "positionNumber": 45,
 *                             "quarter": 1,
 *                             "down": 2,
 *                             "target": "12",
 *                             "myteamId": 1,
 *                             "oppteamId": 2,
 *                             "strategies": "regular",
 *                             "weather_status": "Normal",
 *                             "game_id": 36,
 *                             "league_id": 22,
 *                             "time": "12:00",
 *                             "my_points": 7,
 *                             "oponent_points": 0,
 *                             "mode": "practice"
 *                         },
 *                         "selectedPlayIds": 280,
 *                         "play": {"id": 280, "title": "IN 25(4)-RAM ALL-CHECK"},
 *                         "type": "offensive",
 *                         "targetPlayers": {{"value": 101, "text": "RB Player"}},
 *                         "my_team": 1,
 *                         "opponent_team": 2,
 *                         "mode": "practice"
 *                     }
 *                 }
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(
 *         response=422,
 *         description="Caller not linked to a head coach group",
 *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Head coach group is not available for this user."))
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Pusher broadcast failed",
 *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Broadcast failed"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AssistantSystemSuggestionBroadcastRequest",
 *     required={"down", "weather", "strategies", "expected_yardage_gain", "h_mark_position"},
 *     @OA\Property(property="down", type="integer", minimum=1, maximum=4, example=2),
 *     @OA\Property(property="weather", type="string", enum={"Normal", "Rain", "Snow"}, example="Rain", description="Alias: weather_status"),
 *     @OA\Property(property="strategies", type="string", enum={"regular", "red zone", "hurry up", "aggressive", "chew clock"}, example="regular"),
 *     @OA\Property(property="expected_yardage_gain", type="integer", example=10, description="Aliases: expectedyard, yardage"),
 *     @OA\Property(property="h_mark_position", type="string", enum={"hmark_left", "hmark_center", "hmark_right"}, example="hmark_center"),
 *     @OA\Property(property="game_id", type="integer", nullable=true, example=36),
 *     @OA\Property(property="league_id", type="integer", nullable=true, example=22),
 *     @OA\Property(property="mode", type="string", nullable=true, enum={"practice", "play"}, example="practice")
 * )
 *
 * @OA\Schema(
 *     schema="HeadCoachSystemSuggestionPayload",
 *     @OA\Property(property="down", type="integer", example=2),
 *     @OA\Property(property="weather", type="string", example="Rain"),
 *     @OA\Property(property="strategies", type="string", example="regular"),
 *     @OA\Property(property="expected_yardage_gain", type="integer", example=10),
 *     @OA\Property(property="h_mark_position", type="string", example="hmark_center"),
 *     @OA\Property(property="game_id", type="integer", nullable=true, example=36),
 *     @OA\Property(property="league_id", type="integer", nullable=true, example=22),
 *     @OA\Property(property="mode", type="string", nullable=true, example="practice"),
 *     @OA\Property(property="actor_id", type="integer", example=15),
 *     @OA\Property(property="actor_name", type="string", example="Assistant Coach")
 * )
 *
 * @OA\Schema(
 *     schema="AssistantSystemSuggestionBroadcastResponse",
 *     @OA\Property(property="status", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="System suggestion broadcast sent to head coach."),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="head_coach_id", type="integer", example=5),
 *         @OA\Property(property="channel", type="string", example="coach-group.5"),
 *         @OA\Property(property="event", type="string", example="head.coach.suggestion"),
 *         @OA\Property(property="payload", ref="#/components/schemas/HeadCoachSystemSuggestionPayload")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/assistant-coach/system-suggestion/broadcast",
 *     operationId="assistantSystemSuggestionBroadcast",
 *     tags={"Broadcast"},
 *     summary="Assistant coach → head coach: broadcast scoreboard context for System Suggestions",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/AssistantSystemSuggestionBroadcastRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Broadcast dispatched to head coach",
 *         @OA\JsonContent(
 *             ref="#/components/schemas/AssistantSystemSuggestionBroadcastResponse",
 *             example={
 *                 "status": 200,
 *                 "message": "System suggestion broadcast sent to head coach.",
 *                 "data": {
 *                     "head_coach_id": 5,
 *                     "channel": "coach-group.5",
 *                     "event": "head.coach.suggestion",
 *                     "payload": {
 *                         "down": 2,
 *                         "weather": "Rain",
 *                         "strategies": "regular",
 *                         "expected_yardage_gain": 10,
 *                         "h_mark_position": "hmark_center",
 *                         "game_id": 36,
 *                         "league_id": 22,
 *                         "mode": "practice",
 *                         "actor_id": 15,
 *                         "actor_name": "Assistant Coach"
 *                     }
 *                 }
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden — caller is not an assistant coach",
 *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Forbidden"))
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error or assistant not linked to a head coach",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(@OA\Property(property="message", type="string", example="Head coach is not linked to this assistant.")),
 *                 @OA\Schema(
 *                     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *                     @OA\Property(property="errors", type="object")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Pusher broadcast failed",
 *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Broadcast failed"))
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
final class BroadcastApiDoc
{
}
