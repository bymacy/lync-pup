<?php

namespace Tests\Feature\Startup;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoadblockTest extends TestCase
{
    use RefreshDatabase;

    protected function founderUser(): User
    {
        // account_status must be set explicitly here, not left to the
        // database column's own 'Active' default: actingAs() keeps using
        // this exact in-memory model for every request in the test, and
        // Eloquent never re-fetches a model after create() to learn what
        // default a column got at the database level — so an omitted
        // account_status reads back as null in PHP even though the row
        // itself says 'Active', which the 'approved' middleware then
        // treats as not approved and redirects to /login.
        $user = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        Startup::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_founder_can_view_roadblock_submission_page(): void
    {
        $user = $this->founderUser();

        $response = $this->actingAs($user)->get(route('startup.submissions.index'));

        $response->assertOk();
        $response->assertSee('Roadblock Submission');
    }

    public function test_founder_can_submit_roadblock_without_files(): void
    {
        $user = $this->founderUser();

        $response = $this->actingAs($user)->post(route('startup.submissions.store'), [
            'problem_category' => 'Technical Support',
            'description' => 'The system freezes during peak usage.',
        ]);

        $response->assertRedirect(route('startup.submissions.index', ['tab' => 'roadblock']));
        $this->assertDatabaseHas('roadblocks', [
            'problem_category' => 'Technical Support',
            'status' => 'Pending',
        ]);
    }

    public function test_founder_can_submit_roadblock_with_supporting_files(): void
    {
        Storage::fake('public');
        $user = $this->founderUser();

        $response = $this->actingAs($user)->post(route('startup.submissions.store'), [
            'problem_category' => 'Market Research',
            'description' => 'Need help validating our target segment.',
            'supporting_files' => [
                UploadedFile::fake()->image('proof.jpg'),
                UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('roadblock_files', 2);
        $this->assertDatabaseHas('roadblock_files', ['original_filename' => 'notes.pdf', 'is_image' => false]);
        $this->assertDatabaseHas('roadblock_files', ['original_filename' => 'proof.jpg', 'is_image' => true]);
    }

    public function test_roadblock_requires_category_and_description(): void
    {
        $user = $this->founderUser();

        $response = $this->actingAs($user)->post(route('startup.submissions.store'), []);

        $response->assertSessionHasErrors(['problem_category', 'description']);
    }

    public function test_problem_category_other_is_required_when_others_selected(): void
    {
        $user = $this->founderUser();

        $response = $this->actingAs($user)->post(route('startup.submissions.store'), [
            'problem_category' => 'Others',
            'description' => 'Some issue that does not fit the standard categories.',
        ]);

        $response->assertSessionHasErrors(['problem_category_other']);
    }

    public function test_problem_category_other_is_stored_when_provided(): void
    {
        $user = $this->founderUser();

        $this->actingAs($user)->post(route('startup.submissions.store'), [
            'problem_category' => 'Others',
            'problem_category_other' => 'Legal Counseling',
            'description' => 'Need help with a contract dispute.',
        ]);

        $this->assertDatabaseHas('roadblocks', [
            'problem_category' => 'Others',
            'problem_category_other' => 'Legal Counseling',
        ]);
    }

    public function test_submitted_roadblock_only_visible_to_owner(): void
    {
        $user = $this->founderUser();
        $otherUser = $this->founderUser();

        $this->actingAs($user)->post(route('startup.submissions.store'), [
            'problem_category' => 'Others',
            'problem_category_other' => 'Unrelated Case',
            'description' => 'Some unrelated issue only owner should see.',
        ]);

        $response = $this->actingAs($otherUser)->get(route('startup.submissions.index'));

        $response->assertDontSee('Some unrelated issue only owner should see.');
    }

    public function test_admin_cannot_access_founder_roadblock_routes(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('startup.submissions.index'));

        $response->assertForbidden();
    }
}
