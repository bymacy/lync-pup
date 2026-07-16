<?php

namespace Database\Seeders;

use App\Models\Mentor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MentorSeeder extends Seeder
{
    public function run(): void
    {
        $photoPath = $this->generatePlaceholderAvatar('JC', '7f1d3a');

        Mentor::create([
            'honorific' => 'Ms.',
            'first_name' => 'Jennie',
            'last_name' => 'Cruz',
            'full_name' => 'Ms. Jennie Cruz',
            'specialization' => 'Engineering',
            'contact_email' => 'cruz@gmail.com',
            'contact_number' => '09562549512',
            'mentor_photo_path' => $photoPath,
        ]);

        $this->command->info('Sample mentor with photo created.');
    }

    /**
     * Generates a simple colored placeholder avatar with initials,
     * since we have no real photo assets to seed with.
     */
    private function generatePlaceholderAvatar(string $initials, string $hexColor): string
    {
        $size = 400;
        $image = imagecreatetruecolor($size, $size);

        [$r, $g, $b] = sscanf($hexColor, "%02x%02x%02x");
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $bgColor);

        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($initials);
        $textHeight = imagefontheight($fontSize);
        $x = (int) (($size - $textWidth) / 2);
        $y = (int) (($size - $textHeight) / 2);

        // Draw initials multiple times to fake bold/larger text
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dy = -1; $dy <= 1; $dy++) {
                imagestring($image, $fontSize, $x + $dx * 3, $y + $dy * 3, $initials, $textColor);
            }
        }

        $filename = 'mentors/'.uniqid('mentor_').'.png';
        $fullPath = Storage::disk('public')->path($filename);

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory('mentors');

        imagepng($image, $fullPath);
        imagedestroy($image);

        return $filename;
    }
}