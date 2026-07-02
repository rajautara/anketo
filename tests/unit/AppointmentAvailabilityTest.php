<?php

use App\Libraries\AppointmentAvailability as AA;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pure coverage for appointment slot-grid generation + slot validation.
 * No DB / no "now" dependency. 2026-07-10 is a Friday (ISO weekday 5).
 *
 * @internal
 */
final class AppointmentAvailabilityTest extends CIUnitTestCase
{
    private array $config = [
        'weekdays'     => [1, 2, 3, 4, 5],
        'start_time'   => '09:00',
        'end_time'     => '11:00',
        'slot_minutes' => 30,
    ];

    public function testSlotGridGeneration(): void
    {
        // 09:00..11:00 in 30-min steps → last slot start is 10:30 (10:30+30=11:00).
        $this->assertSame(['09:00', '09:30', '10:00', '10:30'], AA::slotsForDate('2026-07-10', $this->config));
    }

    public function testWeekdayFilteringReturnsEmpty(): void
    {
        // 2026-07-11 is Saturday (6), not enabled.
        $this->assertSame([], AA::slotsForDate('2026-07-11', $this->config));
    }

    public function testSlotGridWithDifferentStep(): void
    {
        $cfg = ['weekdays' => [5], 'start_time' => '09:00', 'end_time' => '10:00', 'slot_minutes' => 20];
        $this->assertSame(['09:00', '09:20', '09:40'], AA::slotsForDate('2026-07-10', $cfg));
    }

    public function testBadConfigYieldsNoSlots(): void
    {
        $this->assertSame([], AA::slotsForDate('2026-07-10', ['weekdays' => [5], 'start_time' => 'x', 'end_time' => '11:00', 'slot_minutes' => 30]));
        $this->assertSame([], AA::slotsForDate('2026-07-10', ['weekdays' => [5], 'start_time' => '09:00', 'end_time' => '08:00', 'slot_minutes' => 30]));
        $this->assertSame([], AA::slotsForDate('2026-07-10', ['weekdays' => [5], 'start_time' => '09:00', 'end_time' => '11:00', 'slot_minutes' => 0]));
    }

    public function testBadDateYieldsNoSlots(): void
    {
        $this->assertSame([], AA::slotsForDate('not-a-date', $this->config));
        $this->assertSame([], AA::slotsForDate('2026-13-40', $this->config));
    }

    public function testIsValidSlotAccepts(): void
    {
        $this->assertTrue(AA::isValidSlot('2026-07-10 09:00', $this->config));
        $this->assertTrue(AA::isValidSlot('2026-07-10 10:30', $this->config));
    }

    public function testIsValidSlotRejects(): void
    {
        $this->assertFalse(AA::isValidSlot('2026-07-10 11:00', $this->config), 'off-grid (past last slot)');
        $this->assertFalse(AA::isValidSlot('2026-07-10 09:15', $this->config), 'not on the 30-min grid');
        $this->assertFalse(AA::isValidSlot('2026-07-11 09:00', $this->config), 'wrong weekday');
        $this->assertFalse(AA::isValidSlot('2026-07-10', $this->config), 'missing time');
        $this->assertFalse(AA::isValidSlot('2026-07-10 9:00', $this->config), 'unpadded hour');
        $this->assertFalse(AA::isValidSlot('garbage', $this->config), 'garbage');
    }
}
