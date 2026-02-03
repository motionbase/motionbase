<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LtiNonce extends Model
{
    protected $fillable = [
        'nonce',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function isValid(string $nonce): bool
    {
        $existing = self::where('nonce', $nonce)
            ->where('expires_at', '>', now())
            ->exists();

        if ($existing) {
            return false; // Nonce already used
        }

        // Store nonce to prevent replay
        self::create([
            'nonce' => $nonce,
            'expires_at' => now()->addMinutes(10),
        ]);

        return true;
    }

    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
