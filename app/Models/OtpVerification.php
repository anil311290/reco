<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [
        'identifier',
        'otp',
        'purpose',
        'status',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'created_by_ip',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if OTP is still valid.
     */
    public function isValid(): bool
    {
        return $this->status === 'pending'
            && $this->expires_at->isFuture()
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Check if OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verify the OTP.
     */
    public function verify(string $otp): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->increment('attempts');

        if ($this->otp !== $otp) {
            if ($this->attempts >= $this->max_attempts) {
                $this->update(['status' => 'expired']);
            }
            return false;
        }

        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        return true;
    }

    /**
     * Generate a new OTP.
     */
    public static function generate(string $identifier, string $purpose, int $ttlMinutes = 10): self
    {
        // Invalidate previous OTPs
        static::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        return static::create([
            'identifier' => $identifier,
            'otp' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose' => $purpose,
            'status' => 'pending',
            'expires_at' => now()->addMinutes($ttlMinutes),
            'created_by_ip' => request()->ip(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFor($query, string $identifier, string $purpose)
    {
        return $query->where('identifier', $identifier)->where('purpose', $purpose);
    }
}
