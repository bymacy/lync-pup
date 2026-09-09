<?php

namespace App\Notifications;

/**
 * Sent when an admin rejects the Information Sheet on evaluation day. The
 * founder can still revise and resubmit it — that resubmission then needs a
 * fresh evaluation before it can be decided again, see
 * Startup::evaluationReached().
 */
class InformationSheetRejected extends FounderNotification
{
    public function __construct(private readonly ?string $remarks = null)
    {
    }

    public function title(): string
    {
        return 'Information Sheet not accepted';
    }

    public function body(): string
    {
        return $this->remarks
            ? "TBIDO Form No.001 was not accepted this evaluation: {$this->remarks} You may revise and resubmit it."
            : 'TBIDO Form No.001 was not accepted this evaluation. You may revise and resubmit it.';
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
