<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait CompressesImages
{
    /**
     * Resizes and compresses an uploaded image down to roughly 2MB or less,
     * regardless of original size, so large phone-camera photos are accepted
     * without needing a strict upload size limit.
     */
    private function compressAndStoreImage(UploadedFile $file, string $directory): string
    {
        $maxBytes = 2 * 1024 * 1024;
        $maxDimension = 1200;

        // A single high-resolution phone photo (e.g. a 48MP shot) can need
        // well over PHP's default 128M memory_limit just to decode into GD
        // before we ever get a chance to resize it. Raise the ceiling for
        // this operation only, then restore whatever it was before.
        $previousMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $mime = $file->getMimeType();
            $sourcePath = $file->getRealPath();

            $image = match ($mime) {
                'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
                'image/png' => @imagecreatefrompng($sourcePath),
                'image/gif' => @imagecreatefromgif($sourcePath),
                'image/webp' => @imagecreatefromwebp($sourcePath),
                default => throw new \RuntimeException('Unsupported image type.'),
            };

            if ($image === false) {
                throw new \RuntimeException('The image could not be read. It may be corrupted or too large to process.');
            }

            $width = imagesx($image);
            $height = imagesy($image);

            if ($width > $maxDimension || $height > $maxDimension) {
                $ratio = min($maxDimension / $width, $maxDimension / $height);
                $newWidth = (int) round($width * $ratio);
                $newHeight = (int) round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            $quality = 85;
            do {
                ob_start();
                imagejpeg($image, null, $quality);
                $data = ob_get_clean();
                $quality -= 10;
            } while (strlen($data) > $maxBytes && $quality > 10);

            imagedestroy($image);

            $filename = $directory.'/'.uniqid('img_').'.jpg';
            Storage::disk('public')->put($filename, $data);

            return $filename;
        } finally {
            ini_set('memory_limit', $previousMemoryLimit);
        }
    }
}