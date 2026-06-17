<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Device;
use App\Support\LeagueOwnership;
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
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

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
     * Get a specific device.
     */
    public function show(Request $request, int $leagueId, int $deviceId): BaseResponse
    {
        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $device = $league->devices()->where('devices.id', $deviceId)->with(['team', 'user'])->first();

        if (!$device) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Device not found or not associated with this league'
            );
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Device retrieved successfully',
            $device
        );
    }

    /**
     * Update a device.
     */
    public function update(Request $request, int $leagueId, int $deviceId): BaseResponse
    {
        $validated = $request->validate([
            'device_name' => ['sometimes', 'string', 'max:255'],
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'status' => ['sometimes', 'in:pending,registered,inactive'],
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
     * Regenerate pairing code for a device.
     */
    public function regeneratePairingCode(Request $request, int $leagueId, int $deviceId): BaseResponse
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
            $device->pairing_code = Device::generateUniquePairingCode();
            $device->qr_token = Device::generateUniqueQrToken();
            $device->status = 'pending';
            $device->paired_at = null;
            $device->save();

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Pairing code regenerated successfully',
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
     * Get the active device for a league (for game start logic).
     */
    public function getActiveDevice(Request $request, int $leagueId): BaseResponse
    {
        $league = LeagueOwnership::leagueForHeadCoach($leagueId);
        LeagueOwnership::assertLeagueOwnedByHeadCoach($league);

        $device = $league->devices()
            ->where('status', 'registered')
            ->with(['team', 'user'])
            ->first();

        if (!$device) {
            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'No active device configured for this league',
                null
            );
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Active device retrieved successfully',
            $device
        );
    }
}
