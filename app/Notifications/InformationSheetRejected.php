<?php

namespace App\Notifications;

/**
 * Sent when an admin rejects the Information Sheet on evaluation day. The
 * founder can still revise and resubmit it — that resubmission then needs a
 * fresh evaluation before it can be decided again, see
 * Startup::evaluationReached(). $deadline (rejected_at + 10 days, see
 * Startup::rejectionDeadline()) is surfaced here too, since founders:
 * purge-expired-rejections removes the account automatically if nothing is
 * resubmitted by then.
 */
class InformationSheetRejected extends FounderNotification
{
    public function __construct(
        private readonly ?string $remarks = null,
        private readonly ?\Illuminate\Support\Carbon $deadline = null,
    ) {
    }

    public function title(): string
    {
        return 'Information Sheet not accepted';
    }

    public function body(): string
    {
        $deadlineNote = $this->deadline
            ? " Resubmit by {$this->deadline->format('F j, Y')} — accounts not resubmitted by then are automatically removed."
            : '';

        return $this->remarks
            ? "TBIDO Form No.001 was not accepted this evaluation: {$this->remarks} You may revise and resubmit it.{$deadlineNote}"
            : "TBIDO Form No.001 was not accepted this evaluation. You may revise and resubmit it.{$deadlineNote}";
    }

    public function route(): string
    {
        return 'startup.information-sheet.edit';
    }

    public function action(): string
    {
        return 'Revise Sheet';
    }

    public function icon(): string
    {
        return 'person-x.svg';
    }
}
