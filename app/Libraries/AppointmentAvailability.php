<?php

namespace App\Libraries;

/**
 * Pure helpers for the "appointment" field: turning an owner's availability
 * config into concrete time slots and validating a chosen slot.
 *
 * Config shape (stored in form_fields.options for appointment fields):
 *   [ 'weekdays' => [1..7 (ISO, Mon=1)], 'start_time' => 'HH:MM',
 *     'end_time' => 'HH:MM', 'slot_minutes' => int, 'date_max_days' => int ]
 *
 * Stored answer format: "Y-m-d H:i" (e.g. "2026-07-10 09:30").
 * Kept free of "now"/DB so it is deterministic and unit-testable; date-range
 * (not-past / within-window) and double-booking checks live in the controller.
 */
class AppointmentAvailability
{
    public const DEFAULT_CONFIG = [
        'weekdays'      => [1, 2, 3, 4, 5],
        'start_time'    => '09:00',
        'end_time'      => '17:00',
        'slot_minutes'  => 30,
        'date_max_days' => 60,
    ];

    /**
     * The "HH:MM" slot grid available on a given date, or [] if the weekday is
     * not enabled or the config is unusable.
     *
     * @return list<string>
     */
    public static function slotsForDate(string $ymd, array $config): array
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $ymd);
        if ($date === false || $date->format('Y-m-d') !== $ymd) {
            return [];
        }

        $weekdays = self::weekdays($config);
        if (! in_array((int) $date->format('N'), $weekdays, true)) {
            return [];
        }

        $start = self::toMinutes($config['start_time'] ?? '');
        $end   = self::toMinutes($config['end_time'] ?? '');
        $step  = (int) ($config['slot_minutes'] ?? 0);

        if ($start === null || $end === null || $step <= 0 || $end <= $start) {
            return [];
        }

        $slots = [];
        for ($t = $start; $t + $step <= $end; $t += $step) {
            $slots[] = self::fromMinutes($t);
        }

        return $slots;
    }

    /**
     * Whether "Y-m-d H:i" is a well-formed value that falls on a valid slot for
     * an enabled weekday under $config.
     */
    public static function isValidSlot(string $value, array $config): bool
    {
        $dt = \DateTime::createFromFormat('!Y-m-d H:i', $value);
        if ($dt === false || $dt->format('Y-m-d H:i') !== $value) {
            return false;
        }

        [$ymd, $time] = explode(' ', $value, 2);

        return in_array($time, self::slotsForDate($ymd, $config), true);
    }

    /**
     * @return list<int>
     */
    private static function weekdays(array $config): array
    {
        $days = $config['weekdays'] ?? [];
        if (! is_array($days)) {
            return [];
        }

        $out = [];
        foreach ($days as $d) {
            $d = (int) $d;
            if ($d >= 1 && $d <= 7) {
                $out[] = $d;
            }
        }

        return $out;
    }

    private static function toMinutes(string $hhmm): ?int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hhmm, $m) !== 1) {
            return null;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }

    private static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
