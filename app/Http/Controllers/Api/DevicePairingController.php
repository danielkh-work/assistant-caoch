<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Device;
use App\Support\DeviceSessionBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevicePairingController extends Controller
{
    /**
     * Pair a physical device using a 4-digit code or QR token.
     */
    public function pair(Request $request): BaseResponse
    {
        $validated = $request->validate([
            'pairing_code' => ['nullable', 'string', 'size:4'],
            'qr_token' => ['nullable', 'string'],
        ]);

        if (empty($validated['pairing_code']) && empty($validated['qr_token'])) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Either pairing_code or qr_token is required.'
            );
        }

        $query = Device::query();

        if (! empty($validated['pairing_code'])) {
            $query->where('pairing_code', $validated['pairing_code']);
        } else {
            $query->where('qr_token', $validated['qr_token']);
        }

        $device = $query->first();

        if (! $device) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Invalid pairing code or QR token.'
            );
        }

        if ($device->status === 'inactive') {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'This device has been deactivated.'
            );
        }

        DB::beginTransaction();

        try {
            $device->tokens()->delete();
            $device->markAsPaired();
            $token = $device->createToken('Device-App-Token')->plainTextToken;

            DB::commit();

            DeviceSessionBroadcaster::broadcastForLeagues($device->fresh(), 'pair', true);

            $device->load('leagues');

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Device paired successfully',
                [
                    'token' => $token,
                    'device' => DeviceSessionBroadcaster::deviceFields($device),
                    'league_ids' => $device->leagues->pluck('id')->values()->all(),
                ]
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
     * Return the authenticated device profile and league associations.
     */
    public function me(Request $request): BaseResponse
    {
        $device = $this->resolveAuthenticatedDevice($request);

        $device->load('leagues');

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Device profile retrieved successfully',
            [
                'device' => DeviceSessionBroadcaster::deviceFields($device),
                'league_ids' => $device->leagues->pluck('id')->values()->all(),
            ]
        );
    }

    private function resolveAuthenticatedDevice(Request $request): Device
    {
        $authenticatable = $request->user();

        if (! $authenticatable instanceof Device) {
            abort(403, 'Device authentication required.');
        }

        return $authenticatable;
    }
}
