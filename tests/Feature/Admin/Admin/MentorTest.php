<?php

namespace Tests\Feature\Admin;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_mentor_index(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('admin.mentors.index'));

        $response->assertOk();
    }

    public function test_startup_user_cannot_view_mentor_index(): void
    {
        $startup = User::factory()->create(['role' => 'Startup']);

        $response = $this->actingAs($startup)->get(route('admin.mentors.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_mentor_index(): void
    {
        $response = $this->get(route('admin.mentors.index'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_create_mentor_with_composed_full_name(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->post(route('admin.mentors.store'), [
            'honorific' => 'Ms.',
            'first_name' => 'Jennie',
            'last_name' => 'Cruz',
            'specialization' => 'Engineering',
            'contact_email' => 'cruz@gmail.com',
            'contact_number' => '09562549512',
        ]);

        $response->assertRedirect(route('admin.mentors.index'));
        $this->assertDatabaseHas('mentors', [
            'first_name' => 'Jennie',
            'last_name' => 'Cruz',
            'full_name' => 'Ms. Jennie Cruz',
        ]);
    }

    public function test_creating_mentor_requires_valid_expertise(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->post(route('admin.mentors.store'), [
            'honorific' => 'Mr.',
            'first_name' => 'John',
            'last_name' => 'Perez',
            'specialization' => 'NotARealOption',
        ]);

        $response->assertSessionHasErrors('specialization');
    }

    public function test_admin_can_update_mentor(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.mentors.update', $mentor), [
            'honorific' => 'Dr.',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'specialization' => 'Business',
        ]);

        $response->assertRedirect(route('admin.mentors.index'));
        $this->assertDatabaseHas('mentors', [
            'mentor_id' => $mentor->mentor_id,
            'full_name' => 'Dr. Updated Name',
        ]);
    }

    public function test_admin_can_delete_mentor(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.mentors.destroy', $mentor));

        $response->assertRedirect(route('admin.mentors.index'));
        $this->assertDatabaseMissing('mentors', ['mentor_id' => $mentor->mentor_id]);
    }
}