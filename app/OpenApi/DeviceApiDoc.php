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
 * @OA\Get(
 *     path="/api/leagues/{league}/devices/active",
 *     operationId="getActiveLeagueDevice",
 *     tags={"Devices"},
 *     summary="Get the active device for a league",
 *     description="Retrieve the active (registered) device for a league. Used by game start logic to determine which device to bind to. Returns null if no active device is configured.",
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
 *         description="Active device retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Active device retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 nullable=true,
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="device_name", type="string", example="My Device"),
 *                 @OA\Property(property="pairing_code", type="string", example="1234"),
 *                 @OA\Property(property="status", type="string", example="registered"),
 *                 @OA\Property(property="team", type="object", nullable=true),
 *                 @OA\Property(property="user", type="object", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="League not found")
 * )
 *
 * @OA\Get(
 *     path="/api/leagues/{league}/devices/{device}",
 *     operationId="getLeagueDevice",
 *     tags={"Devices"},
 *     summary="Get a specific device",
 *     description="Retrieve details of a specific device associated with a league. Requires the league to belong to the authenticated head coach.",
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
 *         description="Device retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Device retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="device_name", type="string", example="My Device"),
 *                 @OA\Property(property="pairing_code", type="string", example="1234"),
 *                 @OA\Property(property="status", type="string", example="registered"),
 *                 @OA\Property(property="team", type="object", nullable=true),
 *                 @OA\Property(property="user", type="object", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="Device not found or not associated with this league")
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
 *     description="Remove a device from a league. If the device is not associated with any other leagues, it will be soft deleted. Requires the league to belong to the authenticated head coach.",
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
 *     path="/api/leagues/{league}/devices/{device}/regenerate-pairing-code",
 *     operationId="regenerateDevicePairingCode",
 *     tags={"Devices"},
 *     summary="Regenerate pairing code for a device",
 *     description="Generate a new 4-digit pairing code and QR token for a device. Resets the device status to 'pending' and clears the paired_at timestamp. Use this when a device needs to be re-paired. Requires the league to belong to the authenticated head coach.",
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
 *         description="Pairing code regenerated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Pairing code regenerated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="device_name", type="string", example="My Device"),
 *                 @OA\Property(property="pairing_code", type="string", example="5678"),
 *                 @OA\Property(property="qr_token", type="string", example="newtoken123"),
 *                 @OA\Property(property="status", type="string", example="pending"),
 *                 @OA\Property(property="paired_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="team", type="object", nullable=true),
 *                 @OA\Property(property="user", type="object", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="League not owned by authenticated head coach"),
 *     @OA\Response(response=404, description="Device not found or not associated with this league")
 * )
 */
final class DeviceApiDoc
{
}
