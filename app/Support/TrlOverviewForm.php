<?php

namespace App\Support;

/**
 * Field definitions for TRL's "Section 1: Startup & Technology Overview" —
 * a one-time intake form that appears above the TRL checklist, but only on
 * the Pre-Assessment stage (Post-Assessment's TRL goes straight to the
 * checklist, same as MRL/TMRL/SRL always have).
 */
class TrlOverviewForm
{
    public const INDUSTRY_FOCUS = ['AI', 'IoT', 'SaaS', 'Supply Chain', 'HealthTech'];

    public const TECH_STACK_FIELDS = [
        'frontend' => 'Frontend',
        'backend' => 'Backend',
        'database' => 'Database',
        'apis' => "API's",
        'frameworks' => 'Frameworks',
    ];

    public const TECHNICAL_CHALLENGES = [
        'Prototype Development', 'System Scalability', 'Performance Optimization',
        'AI Model Accuracy / Data', 'Hardware Reliability', 'Cybersecurity & Data Privacy',
        'Integration Issues', 'Cloud Cost Management', 'Talent / Team Gaps',
    ];

    public const TECH_TEAM_ROLES = [
        'CTO / Tech Lead', 'Developers', 'AI/ML Specialist', 'Hardware Engineer', 'DevOps / Cloud Admin', 'Cybersecurity Expert',
    ];

    public const TEAM_MATURITY_LEVELS = [
        'Concept', 'Functional Prototype', 'MVP (Minimum Viable Product)', 'Production Ready', 'Scalable Production System',
    ];

    public const TESTING_STRATEGIES = ['Unit Testing', 'Integration Testing', 'Automated Testing Framework Used', 'Manual QA Process'];

    public const TOPICS_OF_INTEREST_COLUMN_1 = ['Software Engineering', 'Infrastructure and Engineering', 'AI/Machine Learning', 'Product Design', 'Database Management'];

    public const TOPICS_OF_INTEREST_COLUMN_2 = ['Q/A Testing', 'Cybersecurity', 'IT Project Management', 'UI/UX'];

    public const MODES_OF_COMMUNICATION = ['Face-to-Face', 'Online', 'Hybrid'];
}
