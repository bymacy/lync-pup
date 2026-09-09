<?php

namespace Database\Seeders;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Database\Seeder;

class FounderApplicationSeeder extends Seeder
{
    /**
     * A handful of founder sign-ups sitting in the states the app can
     * actually reach today, so the now-read-only Founder Application page
     * (list + "View" + Delete-while-still-unverified) has something to
     * show across its tabs. Under the current flow, verifying an email
     * auto-activates the account (see VerifyEmailController) — there is no
     * more admin Approve/Reject step — so "Pending" now only ever means
     * "hasn't verified their email yet," and "Rejected" is a state nothing
     * in the app sets anymore. DevDataSeeder's test founders default to
     * account_status=Active, so there's normally nothing sitting in
     * "Pending" here to test against.
     */
    public function run(): void
    {
        // Pending — just signed up, hasn't verified their email yet. Shows
        // the "Not Verified" badge on the View modal and the Delete action
        // (still-unverified junk/test signups only).
        $this->seedFounder('Juan Dela Cruz', 'juan.delacruz@test.com', 'NovaSync PH', 'Pending', verified: false);
        $this->seedFounder('Maria Santos', 'maria.santos@test.com', 'VoidlyTech', 'Pending', verified: false);
        $this->seedFounder('Carlo Ramirez', 'carlo.ramirez@test.com', 'BrightLeaf Agri', 'Pending', verified: false);

        // Verified and auto-activated — already past sign-up, to try the
        // read-only "View" modal on an already-Active row (no Delete
        // action once verified).
        $this->seedFounder('Isabela Cruz', 'isabela.cruz@test.com', 'PixelForge Studios', 'Active', verified: true);
        $this->seedFounder('Marco Villanueva', 'marco.villanueva@test.com', 'DriftWave Labs', 'Active', verified: true);

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
