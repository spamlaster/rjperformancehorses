<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Converts business-local wall times (stored without a zone) to instants,
 * using the same rules as Postgres `timestamp AT TIME ZONE tz`.
 */
final class BusinessTime
{
    /**
     * Unix timestamp for a naive "Y-m-d H:i:s" wall time in $timezone.
     * Returns null for wall times skipped by a spring-forward change. For a
     * repeated fall-back hour, uses the offset in effect after the change (as
     * Postgres does).
     */
    public static function toTimestamp(string $wallTime, string $timezone): ?int
    {
        $zone = new DateTimeZone($timezone);
        $asUtc = (new DateTimeImmutable($wallTime, new DateTimeZone('UTC')))->getTimestamp();

        $offsets = array_unique(array_column($zone->getTransitions($asUtc - 2 * 86400, $asUtc + 2 * 86400), 'offset'));
        $matches = [];
        foreach ($offsets as $offset) {
            $candidate = $asUtc - $offset;
            if ($zone->getOffset(new DateTimeImmutable("@{$candidate}")) === $offset) {
                $matches[] = $candidate;
            }
        }

        return $matches ? max($matches) : null;
    }
}
