<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testWeekendBlockedWhenEnabled(): void
    {
        $this->assertTrue(isMarkingBlockedByCalendar('2026-05-24'));
        $this->assertFalse(isMarkingBlockedByCalendar('2026-05-27'));
    }

    public function testPastDateIsLocked(): void
    {
        $this->assertTrue(isDayLocked('2000-01-01'));
    }

    public function testPublicMarkingOpenByDefault(): void
    {
        $this->assertTrue(isPublicMarkingAllowed());
    }
}
