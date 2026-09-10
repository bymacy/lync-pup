<?php

namespace App\Support;

/**
 * Shared "how will this meeting happen" concept — the same fixed platform
 * list, link placeholders, and per-platform link validation originally
 * built for the Roadblock mentor-assignment modal (see
 * App\Http\Requests\Admin\AssignRoadblockRequest::platformLinkValidator()),
 * factored out here so the evaluation-schedule "Modality" field can reuse
 * the exact same options/validation instead of re-deriving them.
 */
class MeetingPlatform
{
    public const OPTIONS = ['Google Meet', 'Zoom', 'Microsoft Teams', 'Location', 'Custom Link'];

    public const LINK_PLACEHOLDERS = [
        'Google Meet' => 'e.g., https://google.com',
        'Zoom' => 'e.g., https://zoom.us',
        'Microsoft Teams' => 'e.g., Paste Microsoft Teams invitation link here',
        'Location' => 'e.g., 123 Main Street, Suite 400, New York, NY',
        'Custom Link' => 'e.g., https://your-conferencing-app.com',
    ];

    /**
     * Per-platform validation for the link/address field, matching each
     * platform's expected shape:
     *   - Google Meet: must be a google.com (sub)domain link.
     *   - Zoom: must contain a zoom.us "/j/" or "/my/" meeting path.
     *   - Microsoft Teams: loose check for a microsoft.com or live.com link
     *     (Teams links are served from either domain depending on account
     *     type).
     *   - Location: just a minimum-length sanity check — actually verifying
     *     a string "looks like" a real address is unreliable, so this only
     *     guards against obviously-too-short input.
     *   - Custom Link: generic check that it's at least a well-formed
     *     http(s) URL, since it could point anywhere.
     */
    public static function isValidLink(?string $platform, string $value): bool
    {
        $normalized = strtolower(trim($value));

        return match ($platform) {
            'Google Meet' => (bool) preg_match('/:\/\/([a-z0-9-]+\.)*google\.com(\/|$)/i', $value),
            'Zoom' => str_contains($normalized, 'zoom.us/j/') || str_contains($normalized, 'zoom.us/my/'),
            'Microsoft Teams' => str_contains($normalized, 'microsoft.com') || str_contains($normalized, 'live.com'),
            'Location' => mb_strlen(trim($value)) >= 8,
            'Custom Link' => (bool) preg_match('/^https?:\/\//i', trim($value)),
            default => true,
        };
    }

    public static function linkErrorMessage(?string $platform): string
    {
        return match ($platform) {
            'Google Meet' => 'Please enter a valid Google Meet link (e.g. https://meet.google.com/xxx-xxxx-xxx).',
            'Zoom' => 'Please enter a valid Zoom link (must include zoom.us/j/ or zoom.us/my/).',
            'Microsoft Teams' => 'Please enter a valid Microsoft Teams link.',
            'Location' => 'Please enter a more complete address.',
            'Custom Link' => 'Please enter a valid link starting with http:// or https://.',
            default => 'Please enter a valid meeting link or location.',
        };
    }
}
