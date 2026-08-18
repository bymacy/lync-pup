<?php

namespace Database\Seeders;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Database\Seeder;

class FounderApplicationSeeder extends Seeder
{
    /**
     * A handful of founder accounts sitting in different Founder
     * Application states, so the admin Approve/Reject flow can actually be
     * tried out. DevDataSeeder's test founders default to
     * account_status=Active, so there's normally nothing sitting in
     * "Pending" to test against.
     */
    public function run(): void
    {
        // Pending, email already verified — ready to be Approved or Rejected.
        $this->seedFounder('Juan Dela Cruz', 'juan.delacruz@test.com', 'NovaSync PH', 'Pending', verified: true);
        $this->seedFounder('Maria Santos', 'maria.santos@test.com', 'VoidlyTech', 'Pending', verified: true);

        // Pending, but hasn't verified their email yet — shows the "Not
        // Verified" badge on the Review screen.
        $this->seedFounder('Carlo Ramirez', 'carlo.ramirez@test.com', 'BrightLeaf Agri', 'Pending', verified: false);

        // Already decided, to try the read-only "View" modal on the
        // Approved/Rejected tabs.
        $this->seedFounder('Isabela Cruz', 'isabela.cruz@test.com', 'PixelForge Studios', 'Active', verified: true);
        $this->seedFounder('Marco Villanueva', 'marco.villanueva@test.com', 'DriftWave Labs', 'Rejected', verified: true);

        $this->command->info('Founder Application test data seeded — check the "Pending" tab under Founder Application.');
    }

    private function seedFounder(string $name, string $email, string $companyName, string $accountStatus, bool $verified): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => 'Startup',
                'account_status' => $accountStatus,
                'email_verified_at' => $verified ? now() : null,
            ]
        );

        Startup::firstOrCreate(
            ['user_id' => $user->id],
            ['company_name' => $companyName]
        );
    }
}
