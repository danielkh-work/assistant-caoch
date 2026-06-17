<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_name',
        'pairing_code',
        'qr_token',
        'status',
        'team_id',
        'user_id',
        'paired_at',
    ];

    protected $casts = [
        'paired_at' => 'datetime',
    ];

    /**
     * Get the team that owns the device.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user (head coach) that owns the device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leagues associated with this device.
     */
    public function leagues()
    {
        return $this->belongsToMany(League::class, 'league_device', 'device_id', 'league_id')
            ->withTimestamps();
    }

    /**
     * Generate a unique 4-digit pairing code.
     */
    public static function generateUniquePairingCode(): string
    {
        do {
            $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('pairing_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique QR token.
     */
    public static function generateUniqueQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Mark the device as paired.
     */
    public function markAsPaired(): void
    {
        $this->status = 'registered';
        $this->paired_at = now();
        $this->save();
    }

    /**
     * Mark the device as inactive.
     */
    public function markAsInactive(): void
    {
        $this->status = 'inactive';
        $this->save();
    }
}
