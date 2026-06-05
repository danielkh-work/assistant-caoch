<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Play;
use Illuminate\Http\Request;

trait MapsHmarkPlayImage
{
    private static function hMarkPositions(): array
    {
        return ['hmark_left', 'hmark_center', 'hmark_right'];
    }

    protected function hMarkPositionValidationRule(): string
    {
        return 'nullable|string|in:' . implode(',', self::hMarkPositions());
    }

    protected function resolveHMarkPosition(Request $request): string
    {
        $position = $request->input('h_mark_position', 'hmark_center');

        return in_array($position, self::hMarkPositions(), true) ? $position : 'hmark_center';
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
