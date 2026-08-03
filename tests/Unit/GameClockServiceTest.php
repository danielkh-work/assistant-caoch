<?php

namespace Tests\Unit;

use App\Services\GameClockService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class GameClockServiceTest extends TestCase
{
    private GameClockService $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new GameClockService();
    }

    public function test_multi_quarter_catch_up_from_stale_anchor(): void
    {
        // 5 minutes left in Q1, quarter length 15 min, offline 10 minutes
        // → finish Q1 (5), then 5 into Q2 → Q2 with 10 min left
        $now = Carbon::parse('2026-08-04 12:10:00');
        $resolved = $this->clock->resolve([
            'quarter' => 1,
            'timer_remaining' => 5 * 60,
            'sys_time' => '2026-08-04 12:00:00',
            'action' => 'Start',
        ], 15 * 60, 4, $now);

        $this->assertSame(2, $resolved['quarter']);
        $this->assertSame(10 * 60, $resolved['timer_remaining']);
        $this->assertFalse($resolved['exhausted']);
        $this->assertTrue($resolved['advanced']);
        $this->assertSame($now->toDateTimeString(), $resolved['sys_time']);
    }

    public function test_twenty_minute_gap_finishes_full_intervening_quarter(): void
    {
        // 5 left in Q1 + 20 elapsed → finish Q1 and all of Q2 → Q3 full
        $now = Carbon::parse('2026-08-04 12:20:00');
        $resolved = $this->clock->resolve([
            'quarter' => 1,
            'timer_remaining' => 5 * 60,
            'sys_time' => '2026-08-04 12:00:00',
            'action' => 'Start',
        ], 15 * 60, 4, $now);

        $this->assertSame(3, $resolved['quarter']);
        $this->assertSame(15 * 60, $resolved['timer_remaining']);
        $this->assertFalse($resolved['exhausted']);
    }

    public function test_pause_does_not_drain_wall_clock(): void
    {
        $now = Carbon::parse('2026-08-04 12:20:00');
        $resolved = $this->clock->resolve([
            'quarter' => 2,
            'timer_remaining' => 400,
            'sys_time' => '2026-08-04 12:00:00',
            'action' => 'Stop',
        ], 15 * 60, 4, $now);

        $this->assertSame(2, $resolved['quarter']);
        $this->assertSame(400, $resolved['timer_remaining']);
        $this->assertFalse($resolved['advanced']);
        $this->assertSame('2026-08-04 12:00:00', $resolved['sys_time']);
    }

    public function test_resume_anchor_without_prior_sys_time_keeps_remaining(): void
    {
        $now = Carbon::parse('2026-08-04 12:00:00');
        $resolved = $this->clock->resolve([
            'quarter' => 1,
            'timer_remaining' => 500,
            'sys_time' => null,
            'action' => 'Resume',
        ], 15 * 60, 4, $now);

        $this->assertSame(1, $resolved['quarter']);
        $this->assertSame(500, $resolved['timer_remaining']);
        $this->assertFalse($resolved['advanced']);
        $this->assertSame($now->toDateTimeString(), $resolved['sys_time']);
    }

    public function test_exhausts_final_quarter(): void
    {
        $now = Carbon::parse('2026-08-04 13:00:00');
        $resolved = $this->clock->resolve([
            'quarter' => 4,
            'timer_remaining' => 60,
            'sys_time' => '2026-08-04 12:00:00',
            'action' => 'INFO',
        ], 15 * 60, 4, $now);

        $this->assertSame(4, $resolved['quarter']);
        $this->assertSame(0, $resolved['timer_remaining']);
        $this->assertTrue($resolved['exhausted']);
        $this->assertTrue($resolved['advanced']);
    }

    public function test_exact_quarter_boundary_advances_to_full_next_quarter(): void
    {
        $now = Carbon::parse('2026-08-04 12:05:00');
        $resolved = $this->clock->resolve([
            'quarter' => 1,
            'timer_remaining' => 300,
            'sys_time' => '2026-08-04 12:00:00',
            'action' => 'Start',
        ], 15 * 60, 4, $now);

        $this->assertSame(2, $resolved['quarter']);
        $this->assertSame(15 * 60, $resolved['timer_remaining']);
        $this->assertFalse($resolved['exhausted']);
    }
}
