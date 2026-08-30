@php
    $sheet = $startup->informationSheet;
    $v = fn ($val) => $val !== null && $val !== '' ? e($val) : '&nbsp;';
    $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '&nbsp;';
@endphp
@include('admin.exports._letterhead', [
    'formNo' => 'PUP-TBIDO FORM No. 001',
    'title' => 'STARTUP INFORMATION SHEET',
    'instructions' => [
        'Read The Attached Guide to Filling Out the Startup Information Sheet Before Accomplishing the Pup-TBIDO Form No. 100.',
        'Use Capital Letters and Print Legibly. Tick Appropriate Boxes and Use Separate Sheet If Necessary. Indicate N/A If Not Applicable. Do Not Abbreviate.',
        'Date Format (mm/dd/yyyy)',
    ],
])

<div class="section-title">I. FOUNDER'S INFORMATION</div>
<table style="margin-top: 6px;">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <table class="info-table">
                <tr><td class="info-label">1. Surname:</td><td class="info-value">{!! $v($sheet?->surname) !!}</td></tr>
                <tr><td class="info-label">2. First Name:</td><td class="info-value">{!! $v($sheet?->first_name) !!}</td></tr>
                <tr><td class="info-label">3. Middle Name:</td><td class="info-value">{!! $v($sheet?->middle_name) !!}</td></tr>
                <tr><td class="info-label">4. Name Extension:</td><td class="info-value">{!! $v($sheet?->name_extension) !!}</td></tr>
                <tr><td class="info-label">5. Height (m):</td><td class="info-value">{!! $v($sheet?->height_m) !!}</td></tr>
                <tr><td class="info-label">6. Weight (kg):</td><td class="info-value">{!! $v($sheet?->weight_kg) !!}</td></tr>
                <tr><td class="info-label">7. Blood Type:</td><td class="info-value">{!! $v($sheet?->blood_type) !!}</td></tr>
                <tr><td class="info-label">8. GSIS ID No.:</td><td class="info-value">{!! $v($sheet?->gsis_no) !!}</td></tr>
                <tr><td class="info-label">9. Pag-IBIG No.:</td><td class="info-value">{!! $v($sheet?->pagibig_no) !!}</td></tr>
                <tr><td class="info-label">10. PhilHealth No.:</td><td class="info-value">{!! $v($sheet?->philhealth_no) !!}</td></tr>
                <tr><td class="info-label">11. SSS No.:</td><td class="info-value">{!! $v($sheet?->sss_no) !!}</td></tr>
                <tr><td class="info-label">12. Residential Address:</td><td class="info-value">{!! $v($sheet?->residential_address) !!}</td></tr>
                <tr><td class="info-label">13. Permanent Address:</td><td class="info-value">{!! $v($sheet?->permanent_address) !!}</td></tr>
            </table>
        </td>
        <td width="50%" style="vertical-align: top;">
            <table class="info-table">
                <tr><td class="info-label">15. Sex:</td><td class="info-value">{!! $v($sheet?->sex) !!}</td></tr>
                <tr><td class="info-label">16. Civil Status:</td><td class="info-value">{!! $v($sheet?->civil_status) !!}</td></tr>
                <tr><td class="info-label">17. Citizenship (By Birth):</td><td class="info-value">{!! $v($sheet?->citizenship_by_birth) !!}</td></tr>
                <tr><td class="info-label">&nbsp;&nbsp;&nbsp;If Dual Citizenship:</td><td class="info-value">{!! $v($sheet?->citizenship_dual) !!}</td></tr>
                <tr><td class="info-label">18. Place of Birth:</td><td class="info-value">{!! $v($sheet?->place_of_birth) !!}</td></tr>
                <tr><td class="info-label">19. Date of Birth:</td><td class="info-value">{!! $d($sheet?->date_of_birth) !!}</td></tr>
                <tr><td class="info-label">20. Mobile No.:</td><td class="info-value">{!! $v($sheet?->mobile_no) !!}</td></tr>
                <tr><td class="info-label">21. Email Address:</td><td class="info-value">{!! $v($sheet?->founder_email) !!}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="section-title">22. EDUCATIONAL BACKGROUND</div>
<table class="bordered" style="margin-top: 4px;">
    <tr><th>Level</th><th>Name of School</th><th>Degree/Course</th><th>Highest Level/Units</th><th>Year Graduated</th></tr>
    @foreach (['secondary' => 'Secondary', 'vocational' => 'Vocational/Trade', 'college' => 'College', 'graduate' => 'Graduate Studies'] as $key => $label)
    <tr>
        <td>{{ $label }}</td>
        <td>{!! $v($sheet?->{$key.'_school'}) !!}</td>
        <td>{!! $v($sheet?->{$key.'_degree_course'}) !!}</td>
        <td>{!! $v($sheet?->{$key.'_highest_level_unit'}) !!}</td>
        <td>{!! $v($sheet?->{$key.'_year_graduated'}) !!}</td>
    </tr>
    @endforeach
</table>

<div class="section-title">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</div>
<div style="border: 1px solid #000; padding: 6px; min-height: 20px;">{!! nl2br($v($sheet?->scholarships_academic_honors)) !!}</div>

<div class="section-title">24. CORE TEAM FORMATION</div>
<table class="bordered" style="margin-top: 4px;">
    <tr><th>Name</th><th>Designation</th><th>Phone</th><th>Address</th><th>Date of Birth</th><th>Email</th><th>Citizenship</th><th>Sex</th><th>Civil Status</th></tr>
    @forelse ($startup->teamMembers as $member)
    <tr>
        <td>{!! $v($member->full_name) !!}</td>
        <td>{!! $v($member->designation) !!}</td>
        <td>{!! $v($member->phone) !!}</td>
        <td>{!! $v($member->address) !!}</td>
        <td>{!! $d($member->date_of_birth) !!}</td>
        <td>{!! $v($member->email) !!}</td>
        <td>{!! $v($member->citizenship) !!}</td>
        <td>{!! $v($member->sex) !!}</td>
        <td>{!! $v($member->civil_status) !!}</td>
    </tr>
    @empty
    <tr><td colspan="9" class="muted">None listed yet.</td></tr>
    @endforelse
</table>

<div class="section-title">25. INCUBATION INVOLVEMENT</div>
<table class="bordered" style="margin-top: 4px;">
    <tr><th>Organization Name &amp; Address</th><th>From</th><th>To</th><th>No. of Hours</th><th>Incubation Program/Focus</th></tr>
    @forelse ($sheet?->incubationInvolvements ?? [] as $row)
    <tr>
        <td>{!! $v($row->organization_name_address) !!}</td>
        <td>{!! $d($row->date_from) !!}</td>
        <td>{!! $d($row->date_to) !!}</td>
        <td>{!! $v($row->number_of_hours) !!}</td>
        <td>{!! $v($row->incubation_program_focus) !!}</td>
    </tr>
    @empty
    {{-- Sections III, IV and 35 are optional. An empty table is a real answer -
             "nothing to declare" - so it reads as the N/A the paper form asks for
             rather than as an unanswered blank. Nothing is stored for it. --}}
    <tr>@for ($i = 0; $i < 5; $i++)<td>N/A</td>@endfor</tr>
    @endforelse
</table>

<div class="section-title">26. LEARNING &amp; DEVELOPMENT INTERVENTIONS / TRAINING PROGRAMS</div>
<table class="bordered" style="margin-top: 4px;">
    <tr><th>Title</th><th>From</th><th>To</th><th>No. of Hours</th><th>Conducted/Sponsored By</th></tr>
    @forelse ($sheet?->ldInterventions ?? [] as $row)
    <tr>
        <td>{!! $v($row->title) !!}</td>
        <td>{!! $d($row->date_from) !!}</td>
        <td>{!! $d($row->date_to) !!}</td>
        <td>{!! $v($row->number_of_hours) !!}</td>
        <td>{!! $v($row->conducted_sponsored_by) !!}</td>
    </tr>
    @empty
    <tr>@for ($i = 0; $i < 5; $i++)<td>N/A</td>@endfor</tr>
    @endforelse
</table>

<div class="section-title">V. STARTUP INFORMATION</div>
<table style="margin-top: 6px;">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <table class="info-table">
                <tr><td class="info-label">27. Startup Name:</td><td class="info-value">{!! $v($startup->company_name) !!}</td></tr>
                <tr><td class="info-label">28. SEC Registration:</td><td class="info-value">{!! $v($sheet?->sec_registration) !!}</td></tr>
                <tr><td class="info-label">29. Business ID Number:</td><td class="info-value">{!! $v($sheet?->business_id_number) !!}</td></tr>
                <tr><td class="info-label">30. DTI Registration No.:</td><td class="info-value">{!! $v($sheet?->dti_registration_number) !!}</td></tr>
                <tr><td class="info-label">31. Business TIN:</td><td class="info-value">{!! $v($sheet?->business_tin) !!}</td></tr>
            </table>
        </td>
        <td width="50%" style="vertical-align: top;">
            <span class="field-label">33. Startup Overview:</span>
            <div style="border: 1px solid #000; padding: 6px; min-height: 40px; margin-top: 4px;">{!! nl2br($v(filled($sheet?->startup_overview) ? $sheet->startup_overview : $sheet?->business_description)) !!}</div>
        </td>
    </tr>
</table>

<table style="margin-top: 6px;">
    <tr>
        <td width="50%">
            <span class="field-label">32. Non-Academic Distinctions:</span>
            <div style="border: 1px solid #000; padding: 6px; min-height: 24px; margin-top: 4px;">{!! nl2br($v($sheet?->non_academic_distinctions)) !!}</div>
        </td>
        <td width="50%">
            <span class="field-label">34. Membership in Association/Organization:</span>
            <div style="border: 1px solid #000; padding: 6px; min-height: 24px; margin-top: 4px;">{!! nl2br($v($sheet?->membership_associations)) !!}</div>
        </td>
    </tr>
</table>

<div class="section-title">35. REFERENCES</div>
<table class="bordered" style="margin-top: 4px;">
    <tr><th>Name</th><th>Contact</th><th>Email Address</th><th>Address</th></tr>
    @forelse ($sheet?->references ?? [] as $ref)
    <tr>
        <td>{!! $v($ref->name) !!}</td>
        <td>{!! $v($ref->contact) !!}</td>
        <td>{!! $v($ref->email) !!}</td>
        <td>{!! $v($ref->address) !!}</td>
    </tr>
    @empty
    <tr>@for ($i = 0; $i < 4; $i++)<td>N/A</td>@endfor</tr>
    @endforelse
</table>

<div class="section-title">36. DECLARATION</div>
<div style="padding: 8px 0; font-size: 10px; margin-top: 4px;">
    I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement
    pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency
    head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this
    document and its attachments shall cause the filing of administrative/criminal case/s against me.

    <div class="signature-box" style="margin-top: 24px; width: 260px;"></div>
    <div class="signature-caption" style="width: 260px;">Founder's Signature (Sign inside the box)</div>

    <div style="margin-top: 14px;">Date Accomplished: <span class="field-value">{!! $d($sheet?->date_accomplished) !!}</span></div>
</div>

<div style="font-style: italic; font-size: 10px; margin-top: 10px; border-top: 1px solid #000; padding-top: 6px;">For Technology Business Incubation &amp; Development Office Only</div>
<div class="section-heading-plain">Endorsement and Approval</div>
<table style="margin-top: 6px;">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <table class="info-table">
                <tr><td class="info-label">Portfolio Manager:</td><td class="info-value">{!! $v($sheet?->portfolio_manager) !!}</td></tr>
                <tr><td class="info-label">Cohort No.:</td><td class="info-value">{!! $v($sheet?->cohort_no) !!}</td></tr>
                <tr><td class="info-label">Endorsed By:</td><td class="info-value">{!! $v($sheet?->endorsed_by) !!}</td></tr>
                <tr><td class="info-label">Date:</td><td class="info-value">{!! $d($sheet?->endorsement_date) !!}</td></tr>
            </table>
        </td>
        <td width="50%" style="vertical-align: top;">
            <div class="signature-box" style="width: 220px;"></div>
            <div class="signature-caption" style="width: 220px;">Director's Signature (Sign inside the box)</div>
            <div style="margin-top: 8px;">Date of Approval: <span class="field-value">{!! $d($sheet?->director_approval_date) !!}</span></div>
        </td>
    </tr>
</table>
