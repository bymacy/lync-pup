<?php

namespace Tests\Feature\Startup;

use App\Models\Startup;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartupProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeFounderWithStartup(): array
    {
        $user = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);

        return [$user, $startup];
    }

    public function test_founder_can_view_own_profile(): void
    {
        [$user] = $this->makeFounderWithStartup();

        $response = $this->actingAs($user)->get(route('startup.profile.edit'));

        $response->assertOk();
    }

    public function test_admin_cannot_view_founder_profile_route(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('startup.profile.edit'));

        $response->assertForbidden();
    }

    public function test_founder_can_update_own_profile(): void
    {
        [$user, $startup] = $this->makeFounderWithStartup();

        $response = $this->actingAs($user)->patch(route('startup.profile.update'), [
            'company_name' => 'Updated Co',
            'industry_sector' => 'FinTech',
            'business_description' => 'New description',
            'founder_name' => 'Updated Founder',
            'contact_phone' => '09171112222',
            'website' => 'https://updated.ph',
            'location' => 'Makati City',
        ]);

        $response->assertRedirect(route('startup.profile.edit'));
        $this->assertDatabaseHas('startups', ['startup_id' => $startup->startup_id, 'company_name' => 'Updated Co']);
        $this->assertEquals('Updated Founder', $user->fresh()->name);
    }

    public function test_founder_can_add_team_member(): void
    {
        [$user, $startup] = $this->makeFounderWithStartup();

        $response = $this->actingAs($user)->post(route('startup.team-members.store'), [
            'full_name' => 'New Member',
        ]);

        $response->assertRedirect(route('startup.profile.edit'));
        $this->assertDatabaseHas('team_members', ['startup_id' => $startup->startup_id, 'full_name' => 'New Member']);
    }

    public function test_founder_cannot_delete_another_startups_team_member(): void
    {
        [$user] = $this->makeFounderWithStartup();
        $otherStartup = Startup::factory()->create();
        $otherMember = TeamMember::factory()->create(['startup_id' => $otherStartup->startup_id]);

        $response = $this->actingAs($user)->delete(route('startup.team-members.destroy', $otherMember));

        $response->assertForbidden();
    }
}