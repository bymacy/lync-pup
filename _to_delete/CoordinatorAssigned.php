<?php

namespace App\Notifications;

use App\Models\Coordinator;

/**
 * Sent when an admin assigns (or re-assigns) a startup's Portfolio
 * Coordinator. The founder sees who it is on their Startup Profile page,
 * which is where this card points — that page is open at every stage, so the
 * card is never a link to something locked.
 */
class CoordinatorAssigned extends FounderNotification
{
    public function __construct(
        protected Coordinator $coordinator,
        protected bool $replacedPrevious = false,
    ) {
    }

    public function title(): string
    {
        return $this->replacedPrevious
            ? 'New Portfolio Coordinator assigned'
            : 'Portfolio Coordinator assigned';
    }

    public function body(): string
    {
        // display_name is "Ms. Cruz"; name is the full one. Prefer the full
        // name here since this may be the founder's first sight of it, and
        // fall back rather than printing an empty sentence.
        $who = trim((string) ($this->coordinator->name ?: $this->coordinator->display_name))
            ?: 'A Portfolio Coordinator';

        $role = trim((string) $this->coordinator->role_title);

        return $role !== ''
            ? "{$who} ({$role}) is now your Portfolio Coordinator."
            : "{$who} is now your Portfolio Coordinator.";
    }

    public function route(): string
    {
        return 'startup.profile.edit';
    }

    public function action(): string
    {
        return 'View Profile';
    }

    public function icon(): string
    {
        return 'coordProfile.svg';
    }
}
