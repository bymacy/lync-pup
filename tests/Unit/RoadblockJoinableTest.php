<?php

namespace Tests\Unit;

use App\Models\Roadblock;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Regression coverage for the "Join Meeting" button gating. Testers
 * reported it as effectively unclickable because it only appeared during
 * the meeting's exact start–end window (isLive()). It must instead be
 * joinable any time on the meeting's scheduled day (isJoinable()).
 */
class RoadblockJoinableTest extends TestCase
{
    private function roadblockOn(string $date, string $startTime = '09:00', string $endTime = '10:00'): Roadblock
    {
        return new Roadblock([
            'meeting_date' => $date,
            'meeting_start_time' => $startTime,
            'meeting_end_time' => $endTime,
        ]);
    }

    public function test_it_is_joinable_before_the_meetings_start_time_on_the_scheduled_day(): void
    {
        Carbon::setTestNow(Carbon::parse('today 07:00'));

        $roadblock = $this->roadblockOn(now()->toDateString(), '09:00', '10:00');

        $this->assertFalse($roadblock->isLive(), 'sanity check: should not be live yet');
        $this->assertTrue($roadblock->isJoinable());
    }

    public function test_it_is_joinable_after_the_meetings_end_time_but_still_on_the_scheduled_day(): void
    {
        Carbon::setTestNow(Carbon::parse('today 18:00'));

        $roadblock = $this->roadblockOn(now()->toDateString(), '09:00', '10:00');

        $this->assertFalse($roadblock->isLive(), 'sanity check: meeting has already ended');
        $this->assertTrue($roadblock->isJoinable());
    }

    public function test_it_is_joinable_during_the_live_window_too(): void
    {
        Carbon::setTestNow(Carbon::parse('today 09:30'));

        $roadblock = $this->roadblockOn(now()->toDateString(), '09:00', '10:00');

        $this->assertTrue($roadblock->isLive());
        $this->assertTrue($roadblock->isJoinable());
    }

    public function test_it_is_not_joinable_on_a_future_scheduled_day(): void
    {
        Carbon::setTestNow(Carbon::parse('today 09:30'));

        $roadblock = $this->roadblockOn(now()->addDay()->toDateString(), '09:00', '10:00');

        $this->assertFalse($roadblock->isJoinable());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
