<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class DateHelper
{
    public const TZ = 'Asia/Kolkata';

    public const DATE_FORMAT = 'd M Y';

    public const DATETIME_FORMAT = 'd M Y, h:i A';

    /**
     * Calendar date for display (invoice date, due date, opening date, etc.).
     */
    public static function formatDate(null|DateTimeInterface|string $value, string $empty = '-'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        $carbon = $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse($value);

        return $carbon->format(self::DATE_FORMAT);
    }

    /**
     * Date + time in IST (created_at, paid_at, audit logs, etc.).
     */
    public static function formatDateTime(null|DateTimeInterface|string $value, string $empty = '-'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        $carbon = $value instanceof Carbon
            ? $value->copy()->timezone(self::TZ)
            : Carbon::parse($value)->timezone(self::TZ);

        return $carbon->format(self::DATETIME_FORMAT);
    }

    /**
     * Eloquent JSON serialization — date-only at UTC midnight → date; else IST datetime.
     */
    public static function serialize(DateTimeInterface $date): string
    {
        $carbon = Carbon::instance($date);
        $local = $carbon->copy()->timezone(config('app.timezone', self::TZ));

        if ($local->format('H:i:s') === '00:00:00') {
            return $local->format(self::DATE_FORMAT);
        }

        return $carbon->timezone(self::TZ)->format(self::DATETIME_FORMAT);
    }
}
