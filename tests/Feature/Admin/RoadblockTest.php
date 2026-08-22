<?php

namespace Tests\Feature\Admin;

use App\Models\Mentor;
use App\Models\Roadblock;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoadblockTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    protected function pendingRoadblock(): Roadblock
    {
        $user = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);

        return Roadblock::factory()->create([
            'startup_id' => $startup->startup_id,
            'status' => 'Pending',
        ]);
    }

    public function test_admin_can_view_roadblock_management_page(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));

        $response->assertOk();
        $response->assertSee('Roadblock Management');
    }

    public function test_admin_can_assign_mentor_and_schedule_meeting(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), [
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->addDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
        ]);
    }

    public function test_scheduled_roadblock_moves_to_assessment_after_meeting_passes(): void
    {
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $roadblock->update([
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->subDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $this->assertTrue($roadblock->fresh()->isInAssessment());
    }

    /**
     * Resolve/Fail are only allowed once the roadblock is actually awaiting
     * review — either a real "Pending Review" status, or (transitionally)
     * still "Scheduled" with its meeting already in the past. Mirrors
     * test_scheduled_roadblock_moves_to_assessment_after_meeting_passes.
     */
    protected function pendingReviewRoadblock(): Roadblock
    {
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $roadblock->update([
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->subDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        return $roadblock;
    }

    public function test_admin_can_resolve_a_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingReviewRoadblock();

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.resolve', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Resolved',
        ]);
    }

    public function test_admin_can_fail_a_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingReviewRoadblock();

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.fail', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Failed',
        ]);
    }

    public function test_admin_cannot_resolve_a_roadblock_before_its_meeting(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Scheduled']);

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.resolve', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Scheduled',
        ]);
    }

    public function test_admin_can_recover_a_resolved_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Resolved', 'resolved_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.recover', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Pending Review',
        ]);
    }

    public function test_admin_can_delete_a_failed_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Failed', 'failed_at' => now()]);

        $response = $this->actingAs($admin)->delete(route('admin.roadblocks.destroy', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseMissing('roadblocks', ['roadblock_id' => $roadblock->roadblock_id]);
    }

    public function test_founder_cannot_access_admin_roadblock_routes(): void
    {
        $user = User::factory()->create(['role' => 'Startup']);
        Startup::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.roadblocks.index'));

        $response->assertForbidden();
    }

    /**
     * Two separate roadblocks (e.g. two different problems submitted by the
     * same founder) belonging to the same startup, both still Pending —
     * ready to be assigned/scheduled independently in each test below.
     */
    protected function twoPendingRoadblocksForOneStartup(): array
    {
        $user = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);

        return [
            Roadblock::factory()->create(['startup_id' => $startup->startup_id, 'status' => 'Pending']),
            Roadblock::factory()->create(['startup_id' => $startup->startup_id, 'status' => 'Pending']),
        ];
    }

    protected function assignPayload(array $overrides = []): array
    {
        return array_merge([
            'meeting_date' => now()->addDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ], $overrides);
    }

    /**
     * Regression coverage for the "Upcoming Mentorship" double-booking bug
     * testers found: a startup can only be in one meeting at a time, even
     * across two of its own separate roadblocks, and even when each one is
     * assigned to a different mentor/coordinator. The old validation only
     * checked for conflicts on the assignee (mentor_id/coordinator_id), so
     * none of these four combinations were actually blocked before.
     */
    public function test_two_different_mentors_cannot_be_scheduled_for_the_same_startup_at_the_same_time(): void
    {
        $admin = $this->adminUser();
        [$first, $second] = $this->twoPendingRoadblocksForOneStartup();
        [$mentorA, $mentorB] = Mentor::factory()->count(2)->create();

        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $first), $this->assignPayload([
            'mentor_id' => $mentorA->mentor_id,
        ]));

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $second), $this->assignPayload([
            'mentor_id' => $mentorB->mentor_id,
        ]));

        $response->assertSessionHasErrors('meeting_start_time');
        $this->assertDatabaseHas('roadblocks', ['roadblock_id' => $second->roadblock_id, 'status' => 'Pending']);
    }

    public function test_mentor_and_coordinator_cannot_both_be_scheduled_for_the_same_startup_at_the_same_time(): void
    {
        $admin = $this->adminUser();
        [$first, $second] = $this->twoPendingRoadblocksForOneStartup();
        $mentor = Mentor::factory()->create();
        $coordinator = \App\Models\Coordinator::factory()->create();

        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $first), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
        ]));

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $second), $this->assignPayload([
            'coordinator_id' => $coordinator->coordinator_id,
        ]));

        $response->assertSessionHasErrors('meeting_start_time');
        $this->assertDatabaseHas('roadblocks', ['roadblock_id' => $second->roadblock_id, 'status' => 'Pending']);
    }

    public function test_two_different_coordinators_cannot_be_scheduled_for_the_same_startup_at_the_same_time(): void
    {
        $admin = $this->adminUser();
        [$first, $second] = $this->twoPendingRoadblocksForOneStartup();
        [$coordinatorA, $coordinatorB] = \App\Models\Coordinator::factory()->count(2)->create();

        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $first), $this->assignPayload([
            'coordinator_id' => $coordinatorA->coordinator_id,
        ]));

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $second), $this->assignPayload([
            'coordinator_id' => $coordinatorB->coordinator_id,
        ]));

        $response->assertSessionHasErrors('meeting_start_time');
        $this->assertDatabaseHas('roadblocks', ['roadblock_id' => $second->roadblock_id, 'status' => 'Pending']);
    }

    /**
     * The reverse case must still work exactly as before: the same mentor
     * double-booked across two *different* startups at the same time is
     * still correctly blocked by the original assignee-based check.
     */
    public function test_same_mentor_still_cannot_be_double_booked_across_two_different_startups(): void
    {
        $admin = $this->adminUser();
        $first = $this->pendingRoadblock();
        $second = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $first), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
        ]));

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $second), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
        ]));

        $response->assertSessionHasErrors('meeting_start_time');
        $this->assertDatabaseHas('roadblocks', ['roadblock_id' => $second->roadblock_id, 'status' => 'Pending']);
    }

    /**
     * Sanity check that the new startup-scoped conflict rule isn't
     * overly broad: two different startups, two different mentors, same
     * time slot — nothing here should conflict with anything else.
     */
    public function test_two_different_startups_can_be_scheduled_at_the_same_time(): void
    {
        $admin = $this->adminUser();
        $first = $this->pendingRoadblock();
        $second = $this->pendingRoadblock();
        [$mentorA, $mentorB] = Mentor::factory()->count(2)->create();

        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $first), $this->assignPayload([
            'mentor_id' => $mentorA->mentor_id,
        ]));

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $second), $this->assignPayload([
            'mentor_id' => $mentorB->mentor_id,
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('roadblocks', ['roadblock_id' => $second->roadblock_id, 'status' => 'Scheduled']);
    }

    /**
     * Regression test: the "Upcoming Mentorship" list used to sort only by
     * meeting_date, so same-day meetings weren't actually ordered by time.
     * It must be ordered by full start time, with anything currently Live
     * pulled to the top regardless of when it started.
     */
    public function test_upcoming_mentorship_list_is_ordered_by_start_time_with_live_meetings_first(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $admin = $this->adminUser();
        $mentor = Mentor::factory()->create();

        $late = $this->pendingRoadblock();
        $late->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '14:00', 'meeting_end_time' => '15:00',
        ]);

        $early = $this->pendingRoadblock();
        $early->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '09:00', 'meeting_end_time' => '10:00',
        ]);

        // Currently live (started 09:45, ends 10:30) but starts later than
        // $early on the clock — it must still rank first.
        $live = $this->pendingRoadblock();
        $live->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '09:45', 'meeting_end_time' => '10:30',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));

        $response->assertOk();
        $ids = $response->viewData('upcoming')->pluck('roadblock_id')->values()->all();

        $this->assertSame(
            [$live->roadblock_id, $early->roadblock_id, $late->roadblock_id],
            $ids
        );
    }

    /**
     * Same requirement, explicitly for the "Scheduled Today" tab: it's
     * derived by filtering the already-sorted "upcoming" list, so it must
     * inherit the same Live-first / chronological ordering rather than
     * whatever order the underlying query happened to return.
     */
    public function test_scheduled_today_list_is_ordered_by_start_time_with_live_meetings_first(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $admin = $this->adminUser();
        $mentor = Mentor::factory()->create();

        $late = $this->pendingRoadblock();
        $late->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '14:00', 'meeting_end_time' => '15:00',
        ]);

        $early = $this->pendingRoadblock();
        $early->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '09:00', 'meeting_end_time' => '10:00',
        ]);

        // Currently live but starts later on the clock than $early — must
        // still rank first.
        $live = $this->pendingRoadblock();
        $live->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(), 'meeting_start_time' => '09:45', 'meeting_end_time' => '10:30',
        ]);

        // Scheduled for tomorrow — must show up in "upcoming" but never in
        // "Scheduled Today".
        $tomorrow = $this->pendingRoadblock();
        $tomorrow->update([
            'status' => 'Scheduled', 'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->addDay()->toDateString(), 'meeting_start_time' => '09:00', 'meeting_end_time' => '10:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));

        $response->assertOk();
        $ids = $response->viewData('scheduledToday')->pluck('roadblock_id')->values()->all();

        $this->assertSame(
            [$live->roadblock_id, $early->roadblock_id, $late->roadblock_id],
            $ids
        );
        $this->assertNotContains($tomorrow->roadblock_id, $ids);
    }

    /**
     * The two tabs intentionally gate the Join/Edit button swap
     * differently for the very same roadblock row: "Upcoming Mentorship"
     * stays on "Edit" until the meeting's actual start time arrives (it can
     * list rows hours or days out), while "Scheduled Today" already shows
     * "Join" as soon as it's the meeting's day, letting hosts get in early.
     */
    public function test_upcoming_mentorship_stays_editable_before_start_time_while_scheduled_today_shows_join(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $admin = $this->adminUser();
        $mentor = Mentor::factory()->create();

        $roadblock = $this->pendingRoadblock();
        $roadblock->update([
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(),
            'meeting_start_time' => '15:00',
            'meeting_end_time' => '16:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));
        $response->assertOk();

        $html = $response->getContent();
        $upcomingStart = strpos($html, 'Upcoming Mentorship');
        $todayStart = strpos($html, 'Mentorship Today');

        $this->assertNotFalse($upcomingStart);
        $this->assertNotFalse($todayStart);

        $upcomingSection = substr($html, $upcomingStart, $todayStart - $upcomingStart);
        $todaySection = substr($html, $todayStart);

        // Upcoming Mentorship: 3pm hasn't arrived yet (it's 10am) — must
        // still show "Edit", not "Join".
        $this->assertStringContainsString('>Edit<', $upcomingSection);
        $this->assertStringNotContainsString('>Join<', $upcomingSection);

        // Scheduled Today: same roadblock, same 3pm meeting that hasn't
        // started — but this tab shows "Join" already since it's today.
        $this->assertStringContainsString('>Join<', $todaySection);
    }

    /**
     * Regression test: editing an already-Scheduled roadblock (e.g. just to
     * swap the platform) without touching its date/time used to get
     * rejected as "in the past" the moment real time moved past the
     * original value — even though nothing about the schedule itself was
     * being changed.
     */
    public function test_editing_a_scheduled_meeting_without_changing_its_time_does_not_require_a_future_time(): void
    {
        Carbon::setTestNow(Carbon::parse('today 15:00'));

        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $roadblock->update([
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(),
            'meeting_start_time' => '09:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/original',
        ]);

        // Same date/time as already saved (both now well in the past
        // relative to the frozen 15:00 "now") — only the platform changes.
        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->toDateString(),
            'meeting_start_time' => '09:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Zoom',
            'meeting_link' => 'https://zoom.us/j/updated',
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'meeting_platform' => 'Zoom',
        ]);
    }

    public function test_meeting_platform_accepts_location_for_in_person_meetings(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
            'meeting_platform' => 'Location',
            'meeting_link' => 'TBIDO Office, 3rd Floor',
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'meeting_platform' => 'Location',
            'meeting_link' => 'TBIDO Office, 3rd Floor',
        ]);
    }

    /**
     * Regression test: old()/$errors are global to the request, not scoped
     * per roadblock. A validation failure on one roadblock's Assign &
     * Schedule modal used to bleed its stale submitted values into every
     * other roadblock's modal on the same page, since the component didn't
     * check whether the failed submission was actually for that roadblock.
     */
    public function test_a_failed_assignment_does_not_leak_its_values_into_a_different_roadblocks_modal(): void
    {
        $admin = $this->adminUser();
        $target = $this->pendingRoadblock();
        $other = $this->pendingRoadblock();

        $distinctiveLink = 'https://leak-check.example.com/should-only-appear-once';

        // Missing mentor/coordinator entirely — fails required_without.
        $this->actingAs($admin)->put(route('admin.roadblocks.assign', $target), $this->assignPayload([
            'meeting_platform' => 'Google Meet',
            'meeting_link' => $distinctiveLink,
        ]));

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), $distinctiveLink));
    }

    /**
     * Regression test: coordinator_id used to carry its own
     * required_without:mentor_id rule too, so leaving both blank failed
     * both fields at once and showed "Please select a mentor or
     * coordinator." twice. Only one of the two fields should ever raise it.
     */
    public function test_leaving_both_mentor_and_coordinator_blank_shows_only_one_error(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload());

        $response->assertSessionHasErrors('mentor_id');
        $response->assertSessionDoesntHaveErrors('coordinator_id');
    }

    /**
     * Regression test: Carbon::parse('') silently returns "now" rather than
     * failing, so leaving meeting_date blank while picking the current time
     * for meeting_start_time used to also trip the "already passed today"
     * check on start time. Only meeting_date should error here.
     */
    public function test_blank_meeting_date_does_not_also_trigger_a_start_time_error(): void
    {
        Carbon::setTestNow(Carbon::parse('today 10:00'));

        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => '',
            'meeting_start_time' => '10:00',
        ]));

        $response->assertSessionHasErrors('meeting_date');
        $response->assertSessionDoesntHaveErrors('meeting_start_time');
    }

    /**
     * Regression test: leaving both time fields blank used to only surface
     * a "required" error on meeting_start_time — meeting_end_time needs its
     * own independent required error too.
     */
    public function test_leaving_both_time_fields_blank_errors_on_both(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
            'meeting_start_time' => '',
            'meeting_end_time' => '',
        ]));

        $response->assertSessionHasErrors(['meeting_start_time', 'meeting_end_time']);
    }

    public function test_meeting_link_validation_message_mentions_location(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), $this->assignPayload([
            'mentor_id' => $mentor->mentor_id,
            'meeting_link' => '',
        ]));

        $response->assertSessionHasErrors(['meeting_link' => 'meeting link / location']);
    }
}
