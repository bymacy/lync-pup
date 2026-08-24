<?php

namespace App\Support;

/**
 * Static field/table definitions for the Active-Assessment stage's
 * Document 6, 7, and 8 forms — same idea as ReadinessRubric, but for
 * free-form data-entry documents rather than a checklist rubric. The
 * actual per-startup values are stored on AssessmentDocument.data (JSON).
 */
class ActiveAssessmentForms
{
    /**
     * Document 6 — "Startup Growth Strategy (DAGITAB Program)".
     */
    public const DOCUMENT_6_BUSINESS_STAGES = ['Ideation', 'Validation', 'Early Revenue', 'Growth'];

    public const DOCUMENT_6_ROW_COLUMNS = [
        'topics' => 'Topics',
        'objective' => 'Objective',
        'timeline' => 'Timeline',
        'success_indicator' => 'Success Indicator',
    ];

    public const DOCUMENT_6_SECTIONS = [
        'digital_learning_session' => ['title' => '(1) Digital Learning Session', 'default_rows' => 7],
        'digital_mentoring_session' => ['title' => '(2) Digital Mentoring Session', 'default_rows' => 7],
        'technology_development_program' => ['title' => '(3) Technology Development Program', 'default_rows' => 7],
    ];

    /**
     * Document 7 — "Weekly Check-ins".
     */
    public const DOCUMENT_7_ROW_COLUMNS = [
        'dates' => 'Dates',
        'area_discussed' => 'Area Discussed (Include: Events attended, TBI Interventions and Resources)',
        'action_plan' => 'Action Plan',
        'feedback_takeaways' => 'Feedback / Takeaways',
        'remarks' => 'Remarks',
    ];

    public const DOCUMENT_7_DEFAULT_ROWS = 10;

    public const DOCUMENT_7_PERFORMANCE_METRICS = ['Revenue', 'No. of Customers', 'Team Members', 'Funding Secured'];

    public const DOCUMENT_7_PERFORMANCE_COLUMNS = [
        'baseline' => 'Baseline',
        'target' => 'Target',
        'current' => 'Current',
        'dates' => 'Dates',
    ];

    /**
     * Document 8 — "Prototype Validation Form".
     */
    public const DOCUMENT_8_PLATFORM_COMPATIBILITY = [
        'Web App', 'Mobile App (Android/iOS)', 'Desktop App', 'Embedded System', 'API-Based', 'Cross-Platform',
    ];

    public const DOCUMENT_8_DEVELOPMENT_STATUS = [
        'Concept Only', 'Wireframes Design', 'Alpha Version', 'Beta Version', 'Fully Functional Prototype', 'Pilot Deployed',
    ];

    public const DOCUMENT_8_IP_STATUS = [
        'Patent Filed', 'Patent Granted', 'Trademark Registered', 'Copyright Registered', 'IP Not Filed Yet',
    ];

    public static function document8RatingCategories(): array
    {
        return [
            'ux' => [
                'title' => 'User Experience (UX) Assessment',
                'criteria' => [
                    'The product was easy to understand and navigate.',
                    'The application/system logic is well-structured.',
                    'Users were able to complete tasks without assistance.',
                    'The system provided clear feedback for user actions.',
                    'Getting started and instructions were easy to follow.',
                    'The interface was accessible for diverse users (language, ability, etc.).',
                ],
            ],
            'branding' => [
                'title' => 'Aestheticness and Branding Assessment',
                'criteria' => [
                    'The overall visual design was aesthetically pleasing.',
                    'Visual elements (icons, fonts, colors) were consistent and professional.',
                    'The design matches the brand or purpose of the product.',
                    'Layouts and transitions enhanced user experience.',
                    'The design evoked a positive emotional response (trust, excitement, professionalism).',
                ],
            ],
            'functionality' => [
                'title' => 'Functionality and Usability Assessment',
                'criteria' => [
                    'All core features worked as expected.',
                    'Key tasks could be completed successfully without errors.',
                    'The system was stable during use (no crashes or freezes).',
                    'Error messages and system prompts were helpful and understandable.',
                    'The product includes useful support/help features.',
                ],
            ],
            'technical_feasibility' => [
                'title' => 'Technical Feasibility',
                'criteria' => [
                    'The system loads quickly and performs smoothly.',
                    'The prototype functions well across different platforms/devices.',
                    "The use of technology (cloud, database, etc.) fits the product's needs",
                    'The prototype is ready for pilot or limited market deployment.',
                    'The system architecture is scalable and can handle increased load or user demand.',
                    'The system has been tested for bugs and shows a high level of reliability.',
                ],
            ],
            'maintainability' => [
                'title' => 'Maintainability and Support Assessment',
                'criteria' => [
                    'The codebase is well-documented and organized.',
                    'The system is modular and easy to update or extend.',
                    'There is a support plan in place for maintenance and bug fixes.',
                    'Logs and monitoring tools are in place for diagnostics.',
                    'The development team follows a consistent version control and deployment process (e.g., using Git, CI/CD pipelines).',
                ],
            ],
            'security' => [
                'title' => 'Technical Safety and Security Evaluation',
                'criteria' => [
                    'The system handles user data securely and responsibly.',
                    'Security protocols (e.g., encryption, authentication) are properly implemented.',
                    'The prototype complies with relevant data privacy laws.',
                    'The system includes error recovery or fallback mechanisms.',
                    'Overall, the platform is technically safe and trustworthy.',
                ],
            ],
        ];
    }

    /**
     * Average of a rating array (1-5 values, null/blank entries skipped),
     * or null when nothing has been rated yet.
     */
    public static function averageRating(array $ratings): ?float
    {
        $rated = array_filter($ratings, fn ($r) => $r !== null && $r !== '');

        return $rated ? round(array_sum($rated) / count($rated), 2) : null;
    }

    /**
     * The score-interpretation label for the "Score Interpretation Guide
     * (Per Average Rating)" scale shown on Document 8.
     */
    public static function scoreInterpretation(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        return match (true) {
            $average >= 5 => 'Excellent',
            $average >= 4 => 'Very Good',
            $average >= 3 => 'Satisfactory',
            $average >= 2 => 'Needs Improvement',
            $average >= 1 => 'Poor',
            default => null,
        };
    }
}
