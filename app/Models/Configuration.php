<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    public const KEY_VALIDATION_RULE = 'regex:/^[A-Za-z0-9_]+$/';

    public const VALUE_MAX_LENGTH = 255;

    public const VALUE_FORMAT_RULE = 'regex:/^[A-Za-z0-9_]+$/';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function normalizeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    public static function valueValidator(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) && ! is_bool($value) && ! is_int($value)) {
                $fail('The value must be a string, boolean, or integer.');

                return;
            }

            $normalized = self::normalizeValue($value);

            if (strlen($normalized) > self::VALUE_MAX_LENGTH) {
                $fail('The value must not be greater than '.self::VALUE_MAX_LENGTH.' characters.');

                return;
            }

            if (! preg_match('/^[A-Za-z0-9_]+$/', $normalized)) {
                $fail('The value may only contain letters, numbers, and underscores.');
            }
        };
    }

    public static function upsertByKey(string $key, mixed $value): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => self::normalizeValue($value)]
        );
    }

    public function toApiArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
