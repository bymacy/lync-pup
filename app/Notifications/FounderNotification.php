<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Shared base for every admin-side change announced to a founder.
 *
 * Every subclass boils down to the same four things — a headline, a sentence,
 * where clicking it should go, and which icon to draw — so they are declared
 * as abstract accessors here rather than each subclass re-implementing an
 * identical toDatabase(). That also guarantees a single payload shape, which
 * matters because the dashboard cards read these rows directly and would
 * otherwise have to defend against per-type drift.
 *
 * Database channel only for now. Adding 'mail' later is a one-line change to
 * via() plus a toMail() per subclass — nothing else has to move.
 */
abstract class FounderNotification extends Notification
{
    use Queueable;

    abstract public function title(): string;

    abstract public function body(): string;

    /** Route name the dashboard card links to. */
    abstract public function route(): string;

    /** Filename under public/images/icons. */
    abstract public function icon(): string;

    /** Label for the dashboard card's action button. */
    abstract public function action(): string;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'route' => $this->route(),
            'action' => $this->action(),
            'icon' => $this->icon(),
        ];
    }
}
