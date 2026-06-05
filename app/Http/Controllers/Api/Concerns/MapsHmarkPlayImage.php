<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Play;
use Illuminate\Http\Request;

trait MapsHmarkPlayImage
{
    private const HMARK_POSITIONS = ['hmark_left', 'hmark_center', 'hmark_right'];

    protected function hMarkPositionValidationRule(): string
    {
        return 'nullable|string|in:' . implode(',', self::HMARK_POSITIONS);
    }

    protected function resolveHMarkPosition(Request $request): string
    {
        $position = $request->input('h_mark_position', 'hmark_center');

        return in_array($position, self::HMARK_POSITIONS, true) ? $position : 'hmark_center';
    }

    protected function mapOffensivePlayImage(Play $play, string $hMarkPosition): array
    {
        $data = $play->toArray();
        $data['image'] = $play->{$hMarkPosition}
            ?? $play->hmark_center
            ?? $play->image
            ?? null;

        unset($data['hmark_left'], $data['hmark_center'], $data['hmark_right']);

        return $data;
    }
}
