<?php

namespace App\Support;

/**
 * Field/table definitions for the Venture Exit stage's "Startup Exit Form"
 * (PUP-TBIDO FORM No.013) — a one-off exit form (checkboxes, a graduation
 * readiness checklist, free-text sections, and a readiness-level summary
 * table), same shape as Documents 6/7/8, so it's stored the same way: as a
 * single AssessmentDocument (document_number = 13) under stage
 * 'Venture Exit', rather than forced into the TRL/MRL/TMRL/SRL rubric model.
 */
class VentureExitForm
{
    public const DOCUMENT_NUMBER = 13;

    public const FORM_NO = 'PUP-TBIDO FORM No.013';

    /**
     * Same options as Document 6's Business Stage checklist.
     */
    public const BUSINESS_STAGES = ActiveAssessmentForms::DOCUMENT_6_BUSINESS_STAGES;

    public const GRADUATION_READINESS_INDICATORS = [
        'Product-Market Fit Achieved',
        'Business Model is Sustainable',
        'Legal Documents and Registrations are in Place',
        'Revenue Generating',
        'Ready to Scale or Raise Funds',
        'Completed Incubation Program without Absents',
    ];
}
