<?php

namespace App\Services\Attendance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ergebnis eines Tages für ein Kind: Intervalle, Umfang und Markierungen.
 */
final class AttendanceDay
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_SICK = 'sick';

    public const STATUS_NOT_EXPECTED = 'not_expected';

    public const STATUS_NO_DATA = 'no_data';

    public const STATUS_LABELS = [
        self::STATUS_PRESENT => 'anwesend',
        self::STATUS_SICK => 'krank gemeldet',
        self::STATUS_NOT_EXPECTED => 'laut Abfrage abgemeldet',
        self::STATUS_NO_DATA => 'keine Angabe',
    ];

    /**
     * @param  array<int, array{from: Carbon, to: Carbon, capped: bool, running: bool}>  $intervals
     * @param  Collection<int, \App\Model\ChildAttendanceEvent>  $events  alle Ereignisse des Tages inkl. stornierter
     */
    public function __construct(
        public readonly Carbon $date,
        public readonly int $minutes,
        public readonly array $intervals,
        public readonly bool $incomplete,
        public readonly bool $running,
        public readonly bool $automatic,
        public readonly bool $holiday,
        public readonly bool $edited,
        public readonly string $status,
        public readonly Collection $events,
    ) {}

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status];
    }

    public function isPresent(): bool
    {
        return $this->status === self::STATUS_PRESENT;
    }

    public static function formatMinutes(int $minutes): string
    {
        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }

    public function formattedDuration(): string
    {
        return self::formatMinutes($this->minutes);
    }
}
