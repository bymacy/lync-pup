<?php

namespace Tests\Unit;

use App\Models\Roadblock;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Coverage for Roadblock::getMeetingStatusLabelAttribute()'s day-out
 * buckets: Soon (Tomorrow) -> Soon (This Week) -> Upcoming (Next Week) ->
 * Upcoming (Next Month) -> an exact date beyond that.
 */
class RoadblockMeetingStatusLabelTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function roadblockDaysOut(int $days): Roadblock
    {
        return new Roadblock([
            'meeting_date' => now()->addDays($days)->toDateString(),
            'meeting_start_time' => '09:00',
            'meeting_end_time' => '10:00',
        ]);
    }

    public function test_tomorrow_is_soon_tomorrow(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $this->assertSame('Soon (Tomorrow)', $this->roadblockDaysOut(1)->meeting_status_label);
    }

    public function test_two_to_six_days_out_is_soon_this_week(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $this->assertSame('Soon (This Week)', $this->roadblockDaysOut(2)->meeting_status_label);
        $this->assertSame('Soon (This Week)', $this->roadblockDaysOut(6)->meeting_status_label);
    }

    public function test_seven_to_thirteen_days_out_is_upcoming_next_week(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $this->assertSame('Upcoming (Next Week)', $this->roadblockDaysOut(7)->meeting_status_label);
        $this->assertSame('Upcoming (Next Week)', $this->roadblockDaysOut(13)->meeting_status_label);
    }

    public function test_fourteen_to_thirty_days_out_is_upcoming_next_month(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $this->assertSame('Upcoming (Next Month)', $this->roadblockDaysOut(14)->meeting_status_label);
        $this->assertSame('Upcoming (Next Month)', $this->roadblockDaysOut(30)->meeting_status_label);
    }

    public function test_beyond_thirty_days_out_shows_the_exact_date(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $roadblock = $this->roadblockDaysOut(31);

        $this->assertSame('Upcoming (' . now()->addDays(31)->format('M j') . ')', $roadblock->meeting_status_label);
    }

    /**
     * Regression guard for the specific bug being fixed: a bucket boundary
     * must not shift just because "now" happens to be late in the day —
     * comparing calendar dates at midnight, not raw elapsed hours.
     */
    public function test_bucket_boundaries_are_stable_regardless_of_time_of_day(): void
    {
        Carbon::setTestNow(Carbon::parse('today 23:30'));

        $this->assertSame('Soon (This Week)', $this->roadblockDaysOut(2)->meeting_status_label);
        $this->assertSame('Upcoming (Next Week)', $this->roadblockDaysOut(7)->meeting_status_label);
    }
}
