<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Devices",
 *     description="Device management per league for parallel game execution"
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/devices",
 *     operationId="getLeagueDevices",
 *     tags={"Devices"},
 *     summary="Get all devices for a league",
 *     description="Retrieve all devices configured for a specific league. Requires the league to belong to the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Devices retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Devices retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="device_name", type="string", example="Device 1"),
 *                     @OA\Property(property="pairing_code", type="string", example="1234"),
 *                     @OA\Property(property="qr_token", type="string", nullable=true),
 *                     @OA\Property(property="status", type="string", enum={"pending", "registered", "inactive"}, example="registered"),
 *                     @OA\Property(property="team_id", type="integer", nullable=true),
 *                     @OA\Property(property="user_id", type="integer", nullable=true),
 *                     @OA\Property(property="paired_at", type="string", format="date-time", nullable=true),
 *                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time"),
 *                     @OA\Property(property="team", type="object", nullable=true),
 *                     @OA\Property(property="user", type="object", nullable=true)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="League not found")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/devices",
 *     operationId="createLeagueDevice",
 *     tags={"Devices"},
 *     summary="Create a new device for a league",
 *     description="Create a new device and associate it with a league. Automatically generates a 4-digit pairing code and QR token. Requires the league to belong to the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"device_name"},
 *             @OA\Property(property="device_name", type="string", example="My Device"),
 *             @OA\Property(property="team_id", type="integer", nullable=true, example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Device created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Device created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="device_name", type="string", example="My Device"),
 *                 @OA\Property(property="pairing_code", type="string", example="1234"),
 *                 @OA\Property(property="qr_token", type="string", example="abc123xyz789"),
 *                 @OA\Property(property="status", type="string", example="pending"),
 *                 @OA\Property(property="team_id", type="integer", nullable=true),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="paired_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time"),
 *                     @OA\Property(property="team", type="object", nullable=true),
 *                     @OA\Property(property="user", type="object", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="League not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Put(
 *     path="/api/leagues/{league}/devices/{device}",
 *     operationId="updateLeagueDevice",
 *     tags={"Devices"},
 *     summary="Update a device",
 *     description="Update a device's properties. Can update device name, team association, or status. Requires the league to belong to the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="device",
 *         in="path",
 *         required=true,
 *         description="Device primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="device_name", type="string", example="Updated Device Name"),
 *             @OA\Property(property="team_id", type="integer", nullable=true, example=2),
 *             @OA\Property(property="status", type="string", enum={"pending", "registered", "inactive"}, example="registered")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Device updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Device updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="device_name", type="string", example="Updated Device Name"),
 *                 @OA\Property(property="pairing_code", type="string", example="1234"),
 *                 @OA\Property(property="status", type="string", example="registered"),
 *                 @OA\Property(property="team", type="object", nullable=true),
 *                 @OA\Property(property="user", type="object", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="Device not found or not associated with this league"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Delete(
 *     path="/api/leagues/{league}/devices/{device}",
 *     operationId="deleteLeagueDevice",
 *     tags={"Devices"},
 *     summary="Delete a device from a league",
 *     description="Remove a device from a league. If the device has an active session, it is logged out first. If the device is not associated with any other leagues, it is deleted. Requires the league to belong to the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="league",
 *         in="path",
 *         required=true,
 *         description="League primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="device",
 *         in="path",
 *         required=true,
 *         description="Device primary key",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Device removed from league successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Device removed from league successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="Device not found or not associated with this league")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/devices/{device}/scan-qr",
 *     operationId="scanLeagueDeviceQr",
 *     tags={"Devices"},
 *     summary="Pair mobile session to a league device",
 *     description="Head coach scans the QR code displayed on the mobile app. Broadcasts `session.approved` to `qb-user.{session_id}` and `qb.session.updated` to `headcoach.{headCoachId}.league.{leagueId}.qb`.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer", example=22)),
 *     @OA\Parameter(name="device", in="path", required=true, @OA\Schema(type="integer", example=5)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(@OA\Property(property="session_id", type="string", format="uuid"))
 *     ),
 *     @OA\Response(response=201, description="Device paired and token issued")
 * )
 *
 * @OA\Post(
 *     path="/api/leagues/{league}/devices/{device}/logout",
 *     operationId="logoutLeagueDevice",
 *     tags={"Devices"},
 *     summary="Log out a device session",
 *     description="Single logout endpoint for head coach dashboard and mobile app. Revokes device tokens, broadcasts `session.logout` to the mobile app and `qb.session.updated` with `is_loggin=false` to the web dashboard. Callable by the authenticated head coach or by the device itself (Sanctum device token).",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="league", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="device", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         @OA\JsonContent(@OA\Property(property="session_id", type="string", format="uuid", nullable=true))
 *     ),
 *     @OA\Response(response=200, description="Device logged out successfully")
 * )
 *
 * @OA\Post(
 *     path="/api/devices/pair",
 *     operationId="pairDevice",
 *     tags={"Devices"},
 *     summary="Pair a physical device",
 *     description="Accept a 4-digit pairing code or QR token from the mobile app. Issues a Sanctum device token and returns associated league IDs for Pusher subscription.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="pairing_code", type="string", example="1234"),
 *             @OA\Property(property="qr_token", type="string", example="abc123token")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Device paired successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Device paired successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="token", type="string", example="1|device-token"),
 *                 @OA\Property(property="device", type="object"),
 *                 @OA\Property(property="league_ids", type="array", @OA\Items(type="integer"))
 *             )
 *         )
 *     ),
 *     @OA\Response(response=422, description="Invalid or inactive pairing code")
 * )
 *
 * @OA\Get(
 *     path="/api/devices/me",
 *     operationId="getAuthenticatedDevice",
 *     tags={"Devices"},
 *     summary="Get authenticated device profile",
 *     security={{"sanctum":{}}},
 *     @OA\Response(response=200, description="Device profile retrieved successfully")
 * )
 *
 * @OA\Post(
 *     path="/api/mobile/create-session",
 *     operationId="createMobileDeviceSession",
 *     tags={"Devices"},
 *     summary="Create mobile pairing session",
 *     description="Returns a UUID session_id for the mobile app to encode in its QR code. Optional Bearer token (device) refreshes session_id on an already-paired device when reopening the QR screen.",
 *     @OA\RequestBody(required=false),
 *     @OA\Response(
 *         response=200,
 *         description="Session created",
 *         @OA\JsonContent(
 *             @OA\Property(property="session_id", type="string", format="uuid", example="822fc835-75aa-48bf-8473-354a4913aab2")
 *         )
 *     )
 * )
 */
final class DeviceApiDoc
{
}
