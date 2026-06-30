<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate and send OTP.
     */
    public function sendOtp(string $identifier, string $purpose, int $ttlMinutes = 10): OtpVerification
    {
        $otp = OtpVerification::generate($identifier, $purpose, $ttlMinutes);

        // Send OTP based on identifier type
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->sendEmailOtp($identifier, $otp->otp, $purpose);
        } else {
            $this->sendSmsOtp($identifier, $otp->otp, $purpose);
        }

        return $otp;
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(string $identifier, string $otp, string $purpose): bool
    {
        $verification = OtpVerification::for($identifier, $purpose)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$verification) {
            return false;
        }

        return $verification->verify($otp);
    }

    /**
     * Check if OTP is verified.
     */
    public function isVerified(string $identifier, string $purpose): bool
    {
        return OtpVerification::for($identifier, $purpose)
            ->where('status', 'verified')
            ->where('verified_at', '>', now()->subMinutes(30))
            ->exists();
    }

    /**
     * Send OTP via email.
     */
    protected function sendEmailOtp(string $email, string $otp, string $purpose): void
    {
        // TODO: Implement actual email sending
        // For now, log the OTP
        Log::info("OTP for {$email} ({$purpose}): {$otp}");
    }

    /**
     * Send OTP via SMS.
     */
    protected function sendSmsOtp(string $phone, string $otp, string $purpose): void
    {
        // TODO: Implement actual SMS sending
        Log::info("OTP for {$phone} ({$purpose}): {$otp}");
    }

    /**
     * Invalidate all pending OTPs for an identifier.
     */
    public function invalidatePending(string $identifier, string $purpose): void
    {
        OtpVerification::for($identifier, $purpose)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);
    }
}
