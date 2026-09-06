<?php

namespace Tests\Feature\Startup;

use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use App\Notifications\NewRoadblockSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoadblockNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression coverage for the new "Notification for new roadblock
     * submission" feature — every Admin user should be notified the moment
     * a founder submits a new roadblock, so it doesn't sit unnoticed in the
     * Pending list.
     */
    public function test_submitting_a_roadblock_notifies_every_admin(): void
    {
        Notification::fake();

        $admin1 = User::factory()->create(['role' => 'Admin']);
        $admin2 = User::factory()->create(['role' => 'Admin']);

        $founder = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create([
            'user_id' => $founder->id,
            'startup_photo_path' => 'startups/photo.jpg',
        ]);
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => 'Approved',
        ]);

        $response = $this->actingAs($founder)->post(route('startup.submissions.store'), [
            'problem_category' => 'Technical Support',
            'description' => 'Need help wiring up the payment gateway.',
        ]);

        $response->assertRedirect(route('startup.submissions.index', ['tab' => 'roadblock']));

        Notification::assertSentTo($admin1, NewRoadblockSubmitted::class);
        Notification::assertSentTo($admin2, NewRoadblockSubmitted::class);
        Notification::assertNotSentTo($founder, NewRoadblockSubmitted::class);
    }
}
