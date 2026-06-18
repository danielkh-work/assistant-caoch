<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Events\MobileSessionApproved;
use App\Models\Device;
use App\Models\User;
use App\Support\DeviceLogoutBroadcaster;
use App\Support\DeviceMobileSession;
use App\Support\DeviceSessionBroadcaster;
use App\Support\LeagueOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    /**
     * Get all devices for a league.
     */
    public function index(Request $request, int $leagueId): BaseResponse
    {
        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $devices = $league->devices()->with(['team', 'user'])->get();
        $devices->each(function (Device $device) {
            $device->setAttribute('is_connected', $device->tokens()->exists());
        });

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Devices retrieved successfully',
            $devices
        );
    }

    /**
     * Store a new device for a league.
     */
    public function store(Request $request, int $leagueId): BaseResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:league_teams,id'],
        ]);

        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        if (! empty($validated['team_id'])) {
            LeagueOwnership::assertTeamBelongsToLeague((int) $validated['team_id'], $leagueId);
        }

        $headCoachId = LeagueOwnership::resolveHeadCoachId();

        DB::beginTransaction();

        try {
            $device = new Device();
            $device->device_name = $validated['device_name'];
            $device->pairing_code = Device::generateUniquePairingCode();
            $device->qr_token = Device::generateUniqueQrToken();
            $device->status = 'pending';
            $device->team_id = $validated['team_id'] ?? null;
            $device->user_id = $headCoachId;
            $device->save();

            $league->devices()->attach($device->id);

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Device created successfully',
                $device->load(['team', 'user'])
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
        }
    }

    /**
     * Update a device.
     */
    public function update(Request $request, int $leagueId, int $deviceId): BaseResponse
    {
        $validated = $request->validate([
            'device_name' => ['sometimes', 'string', 'max:255'],
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:league_teams,id'],
            'status' => ['sometimes', 'string', 'max:50'],
        ]);

        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $device = $league->devices()->where('devices.id', $deviceId)->first();

        if (!$device) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Device not found or not associated with this league'
            );
        }

        if (isset($validated['team_id']) && $validated['team_id'] !== null) {
            LeagueOwnership::assertTeamBelongsToLeague((int) $validated['team_id'], $leagueId);
        }

        DB::beginTransaction();

        try {
            if (isset($validated['device_name'])) {
                $device->device_name = $validated['device_name'];
            }

            if (isset($validated['team_id'])) {
                $device->team_id = $validated['team_id'];
            }

            if (isset($validated['status'])) {
                $device->status = $validated['status'];
                if ($validated['status'] === 'registered' && !$device->paired_at) {
                    $device->paired_at = now();
                }
            }

            $device->save();

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Device updated successfully',
                $device->load(['team', 'user'])
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
        }
    }

    /**
     * Delete a device from a league.
     */
    public function destroy(Request $request, int $leagueId, int $deviceId): BaseResponse
    {
        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $device = $league->devices()->where('devices.id', $deviceId)->first();

        if (!$device) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Device not found or not associated with this league'
            );
        }

        DB::beginTransaction();

        try {
            if ($device->tokens()->exists() || $device->session_id) {
                DeviceLogoutBroadcaster::logoutAndBroadcast($device, auth()->user());
            }

            $league->devices()->detach($device->id);

            if ($device->leagues()->count() === 0) {
                $device->delete();
            }

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Device removed from league successfully'
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
        }
    }

    /**
     * Log out a device session (head coach dashboard or authenticated device).
     * Revokes tokens, broadcasts mobile logout and web `qb.session.updated`.
     */
    public function logout(Request $request, int $leagueId, int $deviceId): BaseResponse
    {
        $authenticatable = $request->user();
        $coach = null;

        if ($authenticatable instanceof Device) {
            if ((int) $authenticatable->id !== $deviceId) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'You can only log out your own device session.'
                );
            }

            $device = $authenticatable;

            if (! $device->leagues()->where('leagues.id', $leagueId)->exists()) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'Device not found or not associated with this league'
                );
            }

            $coach = $device->user_id ? User::find($device->user_id) : null;
        } else {
            $league = LeagueOwnership::leagueForHeadCoach($leagueId);
            LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

            $device = $league->devices()->where('devices.id', $deviceId)->first();

            if (! $device) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'Device not found or not associated with this league'
                );
            }

            $coach = $authenticatable;
        }

        DB::beginTransaction();

        try {
            $payload = DeviceLogoutBroadcaster::logoutAndBroadcast(
                $device,
                $coach,
                array_filter([$request->input('session_id')]),
            );
            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Device logged out successfully',
                $payload
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
        }
    }

    /**
     * Scan the QR code displayed on the mobile app to pair this device.
     */
    public function scanQr(Request $request, int $leagueId, int $deviceId): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $device = $league->devices()->where('devices.id', $deviceId)->first();

        if (! $device) {
            return response()->json([
                'status' => 404,
                'message' => 'Device not found or not associated with this league',
            ], 404);
        }

        $sessionId = $request->string('session_id')->toString();

        DB::beginTransaction();

        try {
            $device->tokens()->delete();
            DeviceMobileSession::bind($device, $sessionId);
            $device->markAsPaired();
            $token = $device->createToken('Device-App-Token')->plainTextToken;

            DB::commit();

            $deviceFields = array_merge(DeviceSessionBroadcaster::deviceFields($device->fresh()), [
                'name' => $device->device_name,
                'code' => $device->pairing_code,
                'head_coach_id' => $device->user_id,
                'is_loggin' => true,
            ]);

            $responseData = [
                'status' => 201,
                'message' => 'Device paired successfully',
                'user' => $deviceFields,
                'user_id' => $device->id,
                'device_id' => $device->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];

            broadcast(new MobileSessionApproved($responseData))->toOthers();
            DeviceSessionBroadcaster::broadcastForLeagues($device->fresh(), 'login', true);

            return response()->json($responseData, 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 400,
                'message' => $th->getMessage(),
            ], 400);
        }
    }

}
