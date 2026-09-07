<?php

namespace App\Services\Exports;

use App\Models\Startup;
use App\Models\ReadinessLevelAssessment;
use App\Support\ReadinessRubric;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Renders a document straight from the REAL PUP-TBIDO Word master instead
 * of a hand-built Blade/DomPDF recreation. Only Document 1 (Startup
 * Information Sheet) has a real master template so far - every other
 * document number falls through (render() returns null) so
 * ExportController can keep using the existing DomPDF/Blade pipeline for
 * those until real masters exist for them too.
 *
 * How this works: storage/app/templates/startup-information-sheet-template.docx
 * is the real master with every fillable value replaced by a "${placeholder}" tag
 * (Word merge-field style) and the three repeating tables (Core Team,
 * Incubation Involvement, L&D Interventions) collapsed down to a single
 * template row each. PHPWord's TemplateProcessor fills the simple
 * placeholders with setValue() and regenerates however many rows those
 * three tables need with cloneRow(). The filled .docx is then converted
 * to PDF by shelling out to LibreOffice - PHPWord's own PDF writer isn't
 * reliable for a layout this complex (real tables, merged cells, a
 * repeating section footer), but LibreOffice renders it exactly as Word
 * would.
 *
 * Requires: `composer require phpoffice/phpword`, and a LibreOffice
 * install reachable at the path in `LIBREOFFICE_PATH` (.env) or one of
 * the common defaults in resolveSofficeBinary() below. Nothing here talks
 * to DomPDF at all.
 */
class WordDocumentExporter
{
    /**
     * Document number -> real master template path (relative to
     * storage/app/templates). Add an entry here once a real Word master
     * exists for that document number - everything else in this class is
     * already generic across documents.
     */
    private const TEMPLATES = [
        1 => 'startup-information-sheet-template.docx',
        2 => 'startup-tech-assessment-trl-template.docx',
        3 => 'startup-tech-assessment-mrl-template.docx',
        9 => 'startup-post-tech-assessment-trl-template.docx',
    ];

    /**
     * Whether $documentNumber has a real Word master to render from -
     * lets callers (ExportController's PDF Bundle guard in particular)
     * check this without actually rendering anything.
     */
    public function hasTemplate(int $documentNumber): bool
    {
        return isset(self::TEMPLATES[$documentNumber]);
    }

    /**
     * Renders $documentNumber for $startup as a filled PDF, or returns
     * null if there's no real master template for that document number
     * yet (the caller should fall back to the DomPDF/Blade pipeline).
     */
    public function render(int $documentNumber, Startup $startup): ?string
    {
        if (! isset(self::TEMPLATES[$documentNumber])) {
            return null;
        }

        return match ($documentNumber) {
            1 => $this->renderDocument1($startup),
            2 => $this->renderDocument2($startup),
            3 => $this->renderDocument3($startup),
            9 => $this->renderDocument9($startup),
            default => null,
        };
    }

    protected function renderDocument1(Startup $startup): string
    {
        $sheet = $startup->informationSheet;

        $templatePath = storage_path('app/templates/'.self::TEMPLATES[1]);
        $processor = new TemplateProcessor($templatePath);

        $v = fn ($val) => $val !== null && $val !== '' ? (string) $val : '';
        $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '';
        // Founder's Information (1-21) and a few Startup Information fields
        // (27-31, 32, 34, 35) print in all caps on the real form - matches
        // its own printed instruction, "Use Capital Letters and Print
        // Legibly". Applied here rather than relying on how the admin
        // actually typed it into the app.
        $vc = fn ($val) => $val !== null && $val !== '' ? mb_strtoupper((string) $val) : '';

        $processor->setValue('surname', $vc($sheet?->surname));
        $processor->setValue('first_name', $vc($sheet?->first_name));
        $processor->setValue('middle_name', $vc($sheet?->middle_name));
        $processor->setValue('name_extension', $vc($sheet?->name_extension));
        $processor->setValue('height_m', $vc($sheet?->height_m));
        $processor->setValue('weight_kg', $vc($sheet?->weight_kg));
        $processor->setValue('blood_type', $vc($sheet?->blood_type));
        $processor->setValue('gsis_no', $vc($sheet?->gsis_no));
        $processor->setValue('pagibig_no', $vc($sheet?->pagibig_no));
        $processor->setValue('philhealth_no', $vc($sheet?->philhealth_no));
        $processor->setValue('sss_no', $vc($sheet?->sss_no));
        $processor->setValue('residential_address', $vc($sheet?->residential_address));
        $processor->setValue('permanent_address', $vc($sheet?->permanent_address));
        $processor->setValue('sex', $vc($sheet?->sex));
        $processor->setValue('civil_status', $vc($sheet?->civil_status));
        $processor->setValue('citizenship_by_birth', $vc($sheet?->citizenship_by_birth));
        $processor->setValue('citizenship_dual', $vc($sheet?->citizenship_dual));
        $processor->setValue('place_of_birth', $vc($sheet?->place_of_birth));
        $processor->setValue('date_of_birth', $d($sheet?->date_of_birth));
        $processor->setValue('mobile_no', $vc($sheet?->mobile_no));
        $processor->setValue('founder_email', $vc($sheet?->founder_email));

        foreach (['secondary', 'vocational', 'college', 'graduate'] as $key) {
            $processor->setValue("{$key}_school", $v($sheet?->{$key.'_school'}));
            $processor->setValue("{$key}_degree_course", $v($sheet?->{$key.'_degree_course'}));
            $processor->setValue("{$key}_highest_level_unit", $v($sheet?->{$key.'_highest_level_unit'}));
            $processor->setValue("{$key}_year_graduated", $v($sheet?->{$key.'_year_graduated'}));
        }

        // The real form gives this 9 separate small cells (a 3x3 grid), one
        // per award/honor - not one big text block. scholarships_academic_
        // honors is stored as free text with each award on its own line
        // (the old Blade view rendered it with nl2br()), so that's split on
        // newlines here and distributed one-per-cell, up to the 9 the
        // template actually has room for.
        $scholarships = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) $sheet?->scholarships_academic_honors)),
            fn ($line) => $line !== ''
        ));
        for ($i = 1; $i <= 9; $i++) {
            $processor->setValue("scholarship_{$i}", $vc($scholarships[$i - 1] ?? ''));
        }

        $processor->setValue('company_name', $vc($startup->company_name));
        $processor->setValue('sec_registration', $vc($sheet?->sec_registration));
        $processor->setValue('startup_overview', $v(
            filled($sheet?->startup_overview) ? $sheet->startup_overview : $sheet?->business_description
        ));
        $processor->setValue('business_id_number', $vc($sheet?->business_id_number));
        $processor->setValue('dti_registration_number', $vc($sheet?->dti_registration_number));
        $processor->setValue('business_tin', $vc($sheet?->business_tin));
        $processor->setValue('non_academic_distinctions', $vc($sheet?->non_academic_distinctions));
        $processor->setValue('membership_associations', $vc($sheet?->membership_associations));

        $processor->setValue('date_accomplished', $d($sheet?->date_accomplished));
        $processor->setValue('portfolio_manager', $v($sheet?->portfolio_manager));
        $processor->setValue('cohort_no', $v($sheet?->cohort_no));
        $processor->setValue('endorsed_by', $v($sheet?->endorsed_by));
        $processor->setValue('endorsement_date', $d($sheet?->endorsement_date));

        $this->cloneRepeatingRow(
            $processor,
            anchor: 'member_full_name',
            rows: $startup->teamMembers->map(fn ($m) => [
                'member_full_name' => $v($m->full_name),
                'member_designation' => $v($m->designation),
                'member_phone' => $v($m->phone),
                'member_address' => $v($m->address),
                'member_date_of_birth' => $d($m->date_of_birth),
                'member_email' => $v($m->email),
                'member_citizenship' => $v($m->citizenship),
                // Real form's column is just "F" or "M", not the full word.
                'member_sex' => $m->sex ? mb_strtoupper(mb_substr($m->sex, 0, 1)) : '',
                'member_civil_status' => $v($m->civil_status),
            ])->all(),
        );

        $this->cloneRepeatingRow(
            $processor,
            anchor: 'incub_org',
            rows: ($sheet?->incubationInvolvements ?? collect())->map(fn ($row) => [
                'incub_org' => $v($row->organization_name_address),
                'incub_from' => $d($row->date_from),
                'incub_to' => $d($row->date_to),
                'incub_hours' => $v($row->number_of_hours),
                'incub_focus' => $v($row->incubation_program_focus),
            ])->all(),
            blankRowFill: 'N/A',
        );

        $this->cloneRepeatingRow(
            $processor,
            anchor: 'ld_title',
            rows: ($sheet?->ldInterventions ?? collect())->map(fn ($row) => [
                'ld_title' => $v($row->title),
                'ld_from' => $d($row->date_from),
                'ld_to' => $d($row->date_to),
                'ld_hours' => $v($row->number_of_hours),
                'ld_by' => $v($row->conducted_sponsored_by),
            ])->all(),
            blankRowFill: 'N/A',
        );

        $this->cloneRepeatingRow(
            $processor,
            anchor: 'ref_name',
            rows: ($sheet?->references ?? collect())->map(fn ($row) => [
                'ref_name' => $vc($row->name),
                'ref_contact' => $vc($row->contact),
                'ref_email' => $vc($row->email),
                'ref_address' => $vc($row->address),
            ])->all(),
            blankRowFill: 'N/A',
        );

        $tempDir = storage_path('app/tmp-exports');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filledDocxPath = $tempDir.'/'.uniqid('doc1-', true).'.docx';
        $processor->saveAs($filledDocxPath);

        // Was: convert to PDF via LibreOffice here. Dropped for now - on
        // this machine, soffice.bin reliably crashes (STATUS_STACK_BUFFER_
        // OVERRUN inside ucrtbase.dll, confirmed via Windows Event Viewer)
        // no matter how it's invoked from PHP, even though it runs fine by
        // hand in a terminal. Rather than keep chasing that under a
        // deadline, this just returns the filled .docx directly - Macy
        // opens it in Word herself. convertToPdf() below is left in place,
        // unused, in case the LibreOffice crash gets sorted out later and
        // PDF output is worth re-enabling.
        $binary = file_get_contents($filledDocxPath);
        @unlink($filledDocxPath);

        return $binary;
    }

    /**
     * Document 2: Pre-Assessment TRL ("Technology Assessment Form"). Same
     * real-master approach as renderDocument1() - storage/app/templates/
     * startup-tech-assessment-trl-template.docx has every fillable value
     * replaced by a "${placeholder}" tag, including the checkbox glyphs
     * (each one individually wrapped in its own structured document tag in
     * this Google-Docs-exported master, but PHPWord's TemplateProcessor
     * does raw XML text search/replace so that doesn't matter - it finds
     * "${cb_industry_ai}" wherever it sits). No repeating tables here (the
     * TRL rubric table's 27 checkbox cells are all individually tagged,
     * not cloned), so this is entirely setValue() calls, no cloneRow().
     *
     * Section 1 ("Startup & Technology Overview") data lives in
     * ReadinessLevelAssessment::trl_overview (a JSON blob shaped by the
     * Assessment Hub's Alpine form in _assessment.blade.php) - founder/
     * tech_lead/contact_info start out copied from the startup's own
     * records but are then stored (and re-editable) independently here,
     * same as the blade view's own comment explains. The 27 TRL rubric
     * checkboxes come from trl_progress (via progressFor('TRL')), keyed
     * the same [level => [bool, bool, bool]] shape ReadinessRubric expects.
     */
    protected function renderDocument2(Startup $startup): string
    {
        $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Pre-Assessment')
            ->first();

        $overview = $assessment?->trl_overview ?? [];
        $progress = $assessment?->progressFor('TRL') ?? [];

        $templatePath = storage_path('app/templates/'.self::TEMPLATES[2]);
        $processor = new TemplateProcessor($templatePath);

        $v = fn ($val) => $val !== null && $val !== '' ? (string) $val : '';
        $vc = fn ($val) => $val !== null && $val !== '' ? mb_strtoupper((string) $val) : '';
        $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '';
        // Section 1's checkboxes print as a checkbox glyph - a checked
        // box with a check mark inside, not a solid block (Macy: "make
        // the black boxes be checkbox instead"). Section 2's TRL rubric
        // prints a plain checkmark instead, with nothing at all when
        // unchecked (Macy: "lagay na lang a check instead of checkbox")
        // - two different conventions, so two closures rather than one
        // shared $cb.
        $cbBox = fn (bool $isChecked) => $isChecked ? '☑' : '☐';
        $cbCheck = fn (bool $isChecked) => $isChecked ? '✓' : '';
        $inList = fn (array $list, string $needle) => in_array($needle, $list, true);
        $processor->setValue('company_name', $v($startup->company_name));
        $processor->setValue('assessment_date', $d($assessment?->assessment_date));
        $processor->setValue('founder', $v($overview['founder'] ?? ''));
        $processor->setValue('contact_info', $v($overview['contact_info'] ?? ''));
        $processor->setValue('tech_lead', $v($overview['tech_lead'] ?? ''));

        $industryFocus = $overview['industry_focus'] ?? [];
        $processor->setValue('cb_industry_ai', $cbBox($inList($industryFocus, 'AI')));
        $processor->setValue('cb_industry_iot', $cbBox($inList($industryFocus, 'IoT')));
        $processor->setValue('cb_industry_saas', $cbBox($inList($industryFocus, 'SaaS')));
        $processor->setValue('cb_industry_supply_chain', $cbBox($inList($industryFocus, 'Supply Chain')));
        $processor->setValue('cb_industry_healthtech', $cbBox($inList($industryFocus, 'HealthTech')));
        $processor->setValue('cb_industry_other', $cbBox((bool) ($overview['industry_focus_other_enabled'] ?? false)));
        $processor->setValue('industry_focus_other_text', $v($overview['industry_focus_other_text'] ?? ''));

        $this->fillMultilineIntoSlots($processor, 'brief_description', $v($overview['brief_description'] ?? ''), 6);
        $this->fillMultilineIntoSlots($processor, 'key_features', $v($overview['key_features'] ?? ''), 6);

        $techStack = $overview['tech_stack'] ?? [];
        $processor->setValue('tech_stack_frontend', $v($techStack['frontend'] ?? ''));
        $processor->setValue('tech_stack_backend', $v($techStack['backend'] ?? ''));
        $processor->setValue('tech_stack_database', $v($techStack['database'] ?? ''));
        $processor->setValue('tech_stack_apis', $v($techStack['apis'] ?? ''));
        $processor->setValue('tech_stack_frameworks', $v($techStack['frameworks'] ?? ''));

        $challenges = $overview['technical_challenges'] ?? [];
        $processor->setValue('cb_challenge_prototype_dev', $cbBox($inList($challenges, 'Prototype Development')));
        $processor->setValue('cb_challenge_scalability', $cbBox($inList($challenges, 'System Scalability')));
        $processor->setValue('cb_challenge_perf_opt', $cbBox($inList($challenges, 'Performance Optimization')));
        $processor->setValue('cb_challenge_ai_accuracy', $cbBox($inList($challenges, 'AI Model Accuracy / Data')));
        $processor->setValue('cb_challenge_hardware', $cbBox($inList($challenges, 'Hardware Reliability')));
        $processor->setValue('cb_challenge_cybersecurity', $cbBox($inList($challenges, 'Cybersecurity & Data Privacy')));
        $processor->setValue('cb_challenge_integration', $cbBox($inList($challenges, 'Integration Issues')));
        $processor->setValue('cb_challenge_cloud_cost', $cbBox($inList($challenges, 'Cloud Cost Management')));
        $processor->setValue('cb_challenge_talent_gaps', $cbBox($inList($challenges, 'Talent / Team Gaps')));
        $processor->setValue('cb_challenge_other', $cbBox((bool) ($overview['technical_challenges_other_enabled'] ?? false)));
        $processor->setValue('challenge_other_text', $v($overview['technical_challenges_other_text'] ?? ''));

        $teamRoles = $overview['tech_team_roles'] ?? [];
        $processor->setValue('tech_team_cto', $v($teamRoles['CTO / Tech Lead'] ?? ''));
        $processor->setValue('tech_team_developers', $v($teamRoles['Developers'] ?? ''));
        $processor->setValue('tech_team_ai_ml', $v($teamRoles['AI/ML Specialist'] ?? ''));
        $processor->setValue('tech_team_hardware', $v($teamRoles['Hardware Engineer'] ?? ''));
        $processor->setValue('tech_team_devops', $v($teamRoles['DevOps / Cloud Admin'] ?? ''));
        $processor->setValue('tech_team_cybersecurity', $v($teamRoles['Cybersecurity Expert'] ?? ''));

        $maturity = $overview['team_maturity_level'] ?? '';
        $processor->setValue('cb_maturity_concept', $cbBox($maturity === 'Concept'));
        $processor->setValue('cb_maturity_functional', $cbBox($maturity === 'Functional Prototype'));
        $processor->setValue('cb_maturity_mvp', $cbBox($maturity === 'MVP (Minimum Viable Product)'));
        $processor->setValue('cb_maturity_production', $cbBox($maturity === 'Production Ready'));
        $processor->setValue('cb_maturity_scalable', $cbBox($maturity === 'Scalable Production System'));

        $testing = $overview['testing_strategies'] ?? [];
        $processor->setValue('cb_testing_unit', $cbBox($inList($testing, 'Unit Testing')));
        $processor->setValue('cb_testing_integration', $cbBox($inList($testing, 'Integration Testing')));
        $processor->setValue('cb_testing_automated', $cbBox($inList($testing, 'Automated Testing Framework')));
        $processor->setValue('testing_framework_name', $v($overview['automated_testing_framework_name'] ?? ''));
        $processor->setValue('cb_testing_manual_qa', $cbBox($inList($testing, 'Manual QA Process')));

        $topics = $overview['topics_of_interest'] ?? [];
        $processor->setValue('cb_topic_software_eng', $cbBox($inList($topics, 'Software Engineering')));
        $processor->setValue('cb_topic_infra_eng', $cbBox($inList($topics, 'Infrastructure and Engineering')));
        $processor->setValue('cb_topic_ai_ml', $cbBox($inList($topics, 'AI/Machine Learning')));
        $processor->setValue('cb_topic_product_design', $cbBox($inList($topics, 'Product Design')));
        $processor->setValue('cb_topic_database_mgmt', $cbBox($inList($topics, 'Database Management')));
        $processor->setValue('cb_topic_qa_testing', $cbBox($inList($topics, 'Q/A Testing')));
        $processor->setValue('cb_topic_cybersecurity', $cbBox($inList($topics, 'Cybersecurity')));
        $processor->setValue('cb_topic_it_pm', $cbBox($inList($topics, 'IT Project Management')));
        $processor->setValue('cb_topic_ui_ux', $cbBox($inList($topics, 'UI/UX')));
        $processor->setValue('cb_topic_operation_support', $cbBox($inList($topics, 'Operation Support')));

        $modes = $overview['mode_of_communication'] ?? [];
        $processor->setValue('cb_mode_face_to_face', $cbBox($inList($modes, 'Face-to-Face')));
        $processor->setValue('cb_mode_online', $cbBox($inList($modes, 'Online')));
        $processor->setValue('cb_mode_hybrid', $cbBox($inList($modes, 'Hybrid')));
        $processor->setValue('cb_mode_other', $cbBox((bool) ($overview['mode_of_communication_other_enabled'] ?? false)));
        $processor->setValue('mode_other_text', $v($overview['mode_of_communication_other_text'] ?? ''));

        foreach (ReadinessRubric::levels('TRL') as $level => $definition) {
            $criteriaChecked = $progress[$level] ?? $progress[(string) $level] ?? [];
            foreach ($definition['criteria'] as $i => $criterion) {
                $processor->setValue("trl_{$level}_{$i}", $cbCheck((bool) ($criteriaChecked[$i] ?? false)));
            }
        }

        $processor->setValue('prepared_by', $vc($assessment?->prepared_by));
        $processor->setValue('prepared_by_position', $v($assessment?->prepared_by_position));
        $processor->setValue('trl_noted_by', $vc($assessment?->trl_noted_by));
        $processor->setValue('trl_noted_by_position', $v($assessment?->trl_noted_by_position));
        $processor->setValue('approved_by', $vc($assessment?->approved_by));

        // approved_by_position is ONE db column but the real form prints
        // the director's title across two lines - split on newline (matches
        // how the admin form's default value for this column is already
        // stored: "Director, ...\nProject Leader, ...").
        $positionLines = preg_split('/\r\n|\r|\n/', (string) ($assessment?->approved_by_position ?? ''));
        $processor->setValue('approved_by_position_1', $v($positionLines[0] ?? ''));
        $processor->setValue('approved_by_position_2', $v($positionLines[1] ?? ''));

        $tempDir = storage_path('app/tmp-exports');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filledDocxPath = $tempDir.'/'.uniqid('doc2-', true).'.docx';
        $processor->saveAs($filledDocxPath);

        $binary = file_get_contents($filledDocxPath);
        @unlink($filledDocxPath);

        return $binary;
    }

    /**
     * Document 3: Pre-Assessment MRL ("Market Readiness Level"). Built from
     * Macy's real official master ("3  OK MRL 1.docx") - like Document 9,
     * this form has no Section 1 startup/tech overview at all, just its own
     * Startup Name / Date header line, the 9-level (4 criteria each) MRL
     * rubric, and its own three-signatory block (Evaluated by / Reviewed
     * by / Noted by - a different shape than TRL's Prepared/Noted/Approved,
     * matching the evaluated_by/reviewed_by/noted_by columns the Assessment
     * Hub's MRL/TMRL block already writes to). Same "plain checkmark,
     * nothing when unchecked" convention as Document 2/9's TRL rubric, and
     * same two-line-position-split trick for the two signatories whose
     * printed title spans two lines.
     */
    protected function renderDocument3(Startup $startup): string
    {
        $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Pre-Assessment')
            ->first();

        $progress = $assessment?->progressFor('MRL') ?? [];

        $templatePath = storage_path('app/templates/'.self::TEMPLATES[3]);
        $processor = new TemplateProcessor($templatePath);

        $v = fn ($val) => $val !== null && $val !== '' ? (string) $val : '';
        $vc = fn ($val) => $val !== null && $val !== '' ? mb_strtoupper((string) $val) : '';
        $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '';
        // Same "plain checkmark, nothing when unchecked" convention as
        // Document 2/9's TRL rubric (Macy: "lagay na lang a check instead
        // of checkbox").
        $cbCheck = fn (bool $isChecked) => $isChecked ? '✓' : '';

        $processor->setValue('company_name', $v($startup->company_name));
        $processor->setValue('assessment_date', $d($assessment?->assessment_date));

        foreach (ReadinessRubric::levels('MRL') as $level => $definition) {
            $criteriaChecked = $progress[$level] ?? $progress[(string) $level] ?? [];
            foreach ($definition['criteria'] as $i => $criterion) {
                $processor->setValue("mrl_{$level}_{$i}", $cbCheck((bool) ($criteriaChecked[$i] ?? false)));
            }
        }

        $processor->setValue('evaluated_by', $vc($assessment?->evaluated_by));
        $evaluatedPositionLines = preg_split('/\r\n|\r|\n/', (string) ($assessment?->evaluated_by_position ?? ''));
        $processor->setValue('evaluated_by_position_1', $v($evaluatedPositionLines[0] ?? ''));
        $processor->setValue('evaluated_by_position_2', $v($evaluatedPositionLines[1] ?? ''));

        $processor->setValue('reviewed_by', $vc($assessment?->reviewed_by));
        $processor->setValue('reviewed_by_position', $v($assessment?->reviewed_by_position));

        $processor->setValue('noted_by', $vc($assessment?->noted_by));
        $notedPositionLines = preg_split('/\r\n|\r|\n/', (string) ($assessment?->noted_by_position ?? ''));
        $processor->setValue('noted_by_position_1', $v($notedPositionLines[0] ?? ''));
        $processor->setValue('noted_by_position_2', $v($notedPositionLines[1] ?? ''));

        $tempDir = storage_path('app/tmp-exports');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filledDocxPath = $tempDir.'/'.uniqid('doc3-', true).'.docx';
        $processor->saveAs($filledDocxPath);

        $binary = file_get_contents($filledDocxPath);
        @unlink($filledDocxPath);

        return $binary;
    }

    /**
     * Document 9: Post-Assessment TRL. Built from Macy's real official
     * master ("9  OK POST TRL.docx") - unlike Document 2, this form has
     * no Section 1 startup/tech overview at all, just the 27-criterion
     * TRL rubric (Section 1 of *this* document, relabeled "POST
     * TECHNOLOGY READINESS LEVEL (TRL)") and its own signatory block, so
     * this method only ever touches a Post-Assessment stage row - never
     * the Pre-Assessment one renderDocument2() reads.
     */
    protected function renderDocument9(Startup $startup): string
    {
        $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Post-Assessment')
            ->first();

        $progress = $assessment?->progressFor('TRL') ?? [];

        $templatePath = storage_path('app/templates/'.self::TEMPLATES[9]);
        $processor = new TemplateProcessor($templatePath);

        $v = fn ($val) => $val !== null && $val !== '' ? (string) $val : '';
        $vc = fn ($val) => $val !== null && $val !== '' ? mb_strtoupper((string) $val) : '';
        // Same "plain checkmark, nothing when unchecked" convention as
        // Document 2's Section 2 TRL rubric (Macy: "lagay na lang a check
        // instead of checkbox").
        $cbCheck = fn (bool $isChecked) => $isChecked ? '✓' : '';

        foreach (ReadinessRubric::levels('TRL') as $level => $definition) {
            $criteriaChecked = $progress[$level] ?? $progress[(string) $level] ?? [];
            foreach ($definition['criteria'] as $i => $criterion) {
                $processor->setValue("trl_{$level}_{$i}", $cbCheck((bool) ($criteriaChecked[$i] ?? false)));
            }
        }

        $processor->setValue('prepared_by', $vc($assessment?->prepared_by));
        $processor->setValue('prepared_by_position', $v($assessment?->prepared_by_position));
        $processor->setValue('trl_noted_by', $vc($assessment?->trl_noted_by));
        $processor->setValue('trl_noted_by_position', $v($assessment?->trl_noted_by_position));
        $processor->setValue('approved_by', $vc($assessment?->approved_by));

        // approved_by_position is ONE db column but the real form prints
        // the director's title across two lines - same split as
        // renderDocument2().
        $positionLines = preg_split('/\r\n|\r|\n/', (string) ($assessment?->approved_by_position ?? ''));
        $processor->setValue('approved_by_position_1', $v($positionLines[0] ?? ''));
        $processor->setValue('approved_by_position_2', $v($positionLines[1] ?? ''));

        $tempDir = storage_path('app/tmp-exports');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filledDocxPath = $tempDir.'/'.uniqid('doc9-', true).'.docx';
        $processor->saveAs($filledDocxPath);

        $binary = file_get_contents($filledDocxPath);
        @unlink($filledDocxPath);

        return $binary;
    }

    /**
     * Splits $text on newlines and drops it one line per "${prefix_N}"
     * placeholder (N = 1..$slots), blanking out any slot beyond however
     * many non-empty lines $text actually had. Backs the two free-text
     * textareas on Document 2's Section 1 (Brief Description, Key
     * Features) that the real form gives 6 fixed blank lines to print on,
     * rather than one flowing paragraph.
     */
    protected function fillMultilineIntoSlots(TemplateProcessor $processor, string $prefix, string $text, int $slots): void
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $text)),
            fn ($line) => $line !== ''
        ));

        for ($i = 1; $i <= $slots; $i++) {
            $processor->setValue("{$prefix}_{$i}", $lines[$i - 1] ?? '');
        }
    }

    /**
     * The file extension render() actually produces for $documentNumber -
     * every Word-backed document is a .docx right now (see the comment
     * above renderDocument1()), never a .pdf, so callers building a
     * filename need to ask rather than assume ".pdf" the way the old
     * DomPDF-only pipeline could.
     */
    public function outputExtension(int $documentNumber): string
    {
        return 'docx';
    }

    /**
     * Clones $anchor's row once per entry in $rows (or once, filled with
     * $blankRowFill in every column, if $rows is empty and a fallback was
     * given) - mirrors the "@empty -> one N/A row" behaviour the old Blade
     * partial used for these same three optional tables. Team Members has
     * no blank-row fallback: an empty team is left as zero rows, matching
     * how the info sheet doesn't force a placeholder founder row.
     */
    protected function cloneRepeatingRow(TemplateProcessor $processor, string $anchor, array $rows, ?string $blankRowFill = null): void
    {
        if (empty($rows) && $blankRowFill !== null) {
            $rows = [array_fill_keys(array_keys($this->rowKeysFor($anchor)), $blankRowFill)];
        }

        $count = max(count($rows), 1);
        $processor->cloneRow($anchor, $count);

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            foreach ($row as $key => $value) {
                $processor->setValue("{$key}#{$n}", $value);
            }
        }

        // cloneRow() always produces at least 1 row even when $rows was
        // genuinely empty and had no blank-row fallback (team members) -
        // blank out that single leftover row's placeholders so it renders
        // as an empty row instead of a literal "${member_full_name#1}".
        if (empty($rows)) {
            foreach (array_keys($this->rowKeysFor($anchor)) as $key) {
                $processor->setValue("{$key}#1", '');
            }
        }
    }

    /**
     * The full set of placeholder keys sharing a row with $anchor, so a
     * blank/leftover row can be cleared or filled in every column, not
     * just the anchor's own.
     */
    protected function rowKeysFor(string $anchor): array
    {
        return match ($anchor) {
            'member_full_name' => array_fill_keys([
                'member_full_name', 'member_designation', 'member_phone', 'member_address',
                'member_date_of_birth', 'member_email', 'member_citizenship', 'member_sex', 'member_civil_status',
            ], true),
            'incub_org' => array_fill_keys(['incub_org', 'incub_from', 'incub_to', 'incub_hours', 'incub_focus'], true),
            'ld_title' => array_fill_keys(['ld_title', 'ld_from', 'ld_to', 'ld_hours', 'ld_by'], true),
            'ref_name' => array_fill_keys(['ref_name', 'ref_contact', 'ref_email', 'ref_address'], true),
            default => [],
        };
    }

    protected function convertToPdf(string $docxPath, string $outDir): string
    {
        $soffice = $this->resolveSofficeBinary();

        // LibreOffice normally figures out its own per-user profile folder
        // from the environment (%APPDATA% on Windows). That works fine
        // when you run soffice yourself in a terminal, but a process
        // spawned by a web server (php artisan serve, PHP-FPM, etc.) often
        // doesn't inherit the same environment - LibreOffice then fails to
        // create/find a profile and exits without producing a PDF or any
        // error text at all. Forcing a specific, app-owned profile
        // directory via -env:UserInstallation sidesteps that entirely -
        // this is the standard fix for "works in my terminal, silently
        // fails from the web app" LibreOffice conversions.
        $profileDir = storage_path('app/tmp-exports/loffice-profile');
        if (! is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }
        $profileUrl = 'file:///'.str_replace('\\', '/', $profileDir);

        // Laravel's Process facade (and Symfony's underneath it) captures
        // output through anonymous pipes. On this machine that makes
        // soffice.bin crash immediately with STATUS_STACK_BUFFER_OVERRUN
        // (0xC0000409) inside ucrtbase.dll - confirmed via Windows Event
        // Viewer, not a guess - some Windows console apps' C runtime
        // mishandles stdout/stderr being a pipe rather than a real console
        // or a file. So this bypasses Process entirely and calls proc_open()
        // directly, redirecting stdin from NUL and stdout/stderr straight to
        // real log files instead of pipes - the combination that avoids
        // whatever soffice.bin/ucrtbase.dll doesn't tolerate here.
        $quote = fn (string $value) => '"'.$value.'"';
        $command = sprintf(
            '%s --headless --norestore -env:UserInstallation=%s --convert-to pdf --outdir %s %s',
            $quote($soffice),
            $quote($profileUrl),
            $quote($outDir),
            $quote($docxPath),
        );

        $stdoutPath = $outDir.'/'.uniqid('soffice-out-', true).'.log';
        $stderrPath = $outDir.'/'.uniqid('soffice-err-', true).'.log';

        $descriptors = [
            0 => ['file', 'NUL', 'r'],
            1 => ['file', $stdoutPath, 'w'],
            2 => ['file', $stderrPath, 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

        if (! is_resource($process)) {
            throw new \RuntimeException('Could not start the LibreOffice conversion process at all (proc_open failed).');
        }

        // proc_open() has no built-in timeout, unlike Process::timeout() -
        // poll proc_get_status() instead and kill it if it runs too long.
        $deadline = microtime(true) + 180;
        do {
            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }
            usleep(200_000);
        } while (microtime(true) < $deadline);

        if ($status['running']) {
            proc_terminate($process);
            proc_close($process);

            throw new \RuntimeException('LibreOffice conversion timed out after 180 seconds.');
        }

        $exitCode = proc_close($process);
        $stdout = @file_get_contents($stdoutPath) ?: '';
        $stderr = @file_get_contents($stderrPath) ?: '';
        @unlink($stdoutPath);
        @unlink($stderrPath);

        if ($exitCode !== 0) {
            Log::error('LibreOffice conversion failed', [
                'exit_code' => $exitCode,
                'error_output' => $stderr,
                'output' => $stdout,
            ]);

            throw new \RuntimeException(
                'Could not convert the filled Word document to PDF (exit code '.$exitCode.'). '
                .'stderr: '.$stderr.' | stdout: '.$stdout
            );
        }

        $pdfPath = $outDir.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';

        if (! is_file($pdfPath)) {
            throw new \RuntimeException("LibreOffice reported success but {$pdfPath} wasn't created.");
        }

        $binary = file_get_contents($pdfPath);
        @unlink($pdfPath);

        return $binary;
    }

    /**
     * LibreOffice's binary name/path differs by OS and isn't always on
     * PATH (Windows in particular almost never adds it). Checked in
     * order: an explicit .env override, then the common install locations
     * for Windows/macOS/Linux, then a bare "soffice" as a last resort in
     * case it IS on PATH after all.
     */
    protected function resolveSofficeBinary(): string
    {
        $candidates = array_filter([
            env('LIBREOFFICE_PATH'),
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // Fall back to bare "soffice" - works if it's genuinely on PATH,
        // and gives a clearer "command not found"-style error otherwise
        // than silently trying a made-up path.
        return 'soffice';
    }
}
