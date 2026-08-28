@php
    $sheet = $startup->informationSheet;
    $v = fn ($val) => $val !== null && $val !== '' ? e($val) : '&nbsp;';
    $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '&nbsp;';
@endphp
@include('admin.exports._letterhead', ['formNo' => 'PUP-TBIDO FORM No. 001', 'title' => 'STARTUP INFORMATION SHEET'])

<div class="section-title">I. FOUNDER'S INFORMATION</div>
<table style="margin-top: 6px;">
    <tr>
        <td width="50%">
            <div class="field-row"><span class="field-label">1. Surname:</span> {!! $v($sheet?->surname) !!}</div>
            <div class="field-row"><span class="field-label">2. First Name:</span> {!! $v($sheet?->first_name) !!}</div>
            <div class="field-row"><span class="field-label">3. Middle Name:</span> {!! $v($sheet?->middle_name) !!}</div>
            <div class="field-row"><span class="field-label">4. Name Extension:</span> {!! $v($sheet?->name_extension) !!}</div>
            <div class="field-row"><span class="field-label">5. Height (m):</span> {!! $v($sheet?->height_m) !!}</div>
            <div class="field-row"><span class="field-label">6. Weight (kg):</span> {!! $v($sheet?->weight_kg) !!}</div>
            <div class="field-row"><span class="field-label">7. Blood Type:</span> {!! $v($sheet?->blood_type) !!}</div>
            <div class="field-row"><span class="field-label">8. GSIS ID No.:</span> {!! $v($sheet?->gsis_no) !!}</div>
            <div class="field-row"><span class="field-label">9. Pag-IBIG No.:</span> {!! $v($sheet?->pagibig_no) !!}</div>
            <div class="field-row"><span class="field-label">10. PhilHealth No.:</span> {!! $v($sheet?->philhealth_no) !!}</div>
            <div class="field-row"><span class="field-label">11. SSS No.:</span> {!! $v($sheet?->sss_no) !!}</div>
            <div class="field-row"><span class="field-label">12. Residential Address:</span> {!! $v($sheet?->residential_address) !!}</div>
            <div class="field-row"><span class="field-label">13. Permanent Address:</span> {!! $v($sheet?->permanent_address) !!}</div>
        </td>
        <td width="50%">
            <div class="field-row"><span class="field-label">15. Sex:</span> {!! $v($sheet?->sex) !!}</div>
            <div class="field-row"><span class="field-label">16. Civil Status:</span> {!! $v($sheet?->civil_status) !!}</div>
            <div class="field-row"><span class="field-label">17. Citizenship (By Birth):</span> {!! $v($sheet?->citizenship_by_birth) !!}</div>
            <div class="field-row"><span class="field-label">&nbsp;&nbsp;&nbsp;If Dual Citizenship:</span> {!! $v($sheet?->citizenship_dual) !!}</div>
            <div class="field-row"><span class="field-label">18. Place of Birth:</span> {!! $v($sheet?->place_of_birth) !!}</div>
            <div class="field-row"><span class="field-label">19. Date of Birth:</span> {!! $d($sheet?->date_of_birth) !!}</div>
            <div class="field-row"><span class="field-label">20. Mobile No.:</span> {!! $v($sheet?->mobile_no) !!}</div>
            <div class="field-row"><span class="field-label">21. Email Address:</span> {!! $v($sheet?->founder_email) !!}</div>
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
<div style="border: 1px solid #9ca3af; padding: 6px; min-height: 20px;">{!! nl2br($v($sheet?->scholarships_academic_honors)) !!}</div>

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
    <tr><td colspan="5" class="muted">None listed yet.</td></tr>
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
    <tr><td colspan="5" class="muted">None listed yet.</td></tr>
    @endforelse
</table>

<div class="section-title">V. STARTUP INFORMATION</div>
<table style="margin-top: 6px;">
    <tr>
        <td width="50%">
            <div class="field-row"><span class="field-label">27. Startup Name:</span> {!! $v($startup->company_name) !!}</div>
            <div class="field-row"><span class="field-label">28. SEC Registration:</span> {!! $v($sheet?->sec_registration) !!}</div>
            <div class="field-row"><span class="field-label">29. Business ID Number:</span> {!! $v($sheet?->business_id_number) !!}</div>
            <div class="field-row"><span class="field-label">30. DTI Registration No.:</span> {!! $v($sheet?->dti_registration_number) !!}</div>
            <div class="field-row"><span class="field-label">31. Business TIN:</span> {!! $v($sheet?->business_tin) !!}</div>
        </td>
        <td width="50%">
            <span class="field-label">33. Startup Overview:</span>
            <div style="border: 1px solid #9ca3af; padding: 6px; min-height: 40px; margin-top: 4px;">{!! nl2br($v($sheet?->business_description)) !!}</div>
        </td>
    </tr>
</table>

<table style="margin-top: 6px;">
    <tr>
        <td width="50%">
            <span class="field-label">32. Non-Academic Distinctions:</span>
            <div style="border: 1px solid #9ca3af; padding: 6px; min-height: 24px; margin-top: 4px;">{!! nl2br($v($sheet?->non_academic_distinctions)) !!}</div>
        </td>
        <td width="50%">
            <span class="field-label">34. Membership in Association/Organization:</span>
            <div style="border: 1px solid #9ca3af; padding: 6px; min-height: 24px; margin-top: 4px;">{!! nl2br($v($sheet?->membership_associations)) !!}</div>
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
    <tr><td colspan="4" class="muted">None listed yet.</td></tr>
    @endforelse
</table>

<div class="section-title">36. DECLARATION</div>
<div style="border: 1px solid #9ca3af; padding: 8px; font-size: 10px; margin-top: 4px;">
    I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement
    pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency
    head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this
    document and its attachments shall cause the filing of administrative/criminal case/s against me.
    <table style="margin-top: 10px;">
        <tr>
            <td width="55%">
                <div class="signature-box">Sign inside the box (print &amp; sign)</div>
                <div style="text-align: center; font-size: 9px;">Founder's Signature</div>
            </td>
            <td width="45%">
                <div class="field-row"><span class="field-label">Date Accomplished:</span> {!! $d($sheet?->date_accomplished) !!}</div>
            </td>
        </tr>
    </table>
</div>

<div style="font-size: 10px; font-weight: bold; margin-top: 10px;">FOR TECHNOLOGY BUSINESS INCUBATION &amp; DEVELOPMENT OFFICE ONLY &mdash; ENDORSEMENT AND APPROVAL</div>
<table style="margin-top: 4px;">
    <tr>
        <td width="50%">
            <div class="field-row"><span class="field-label">Portfolio Manager:</span> {!! $v($sheet?->portfolio_manager) !!}</div>
            <div class="field-row"><span class="field-label">Cohort No.:</span> {!! $v($sheet?->cohort_no) !!}</div>
            <div class="field-row"><span class="field-label">Endorsed By:</span> {!! $v($sheet?->endorsed_by) !!}</div>
            <div class="field-row"><span class="field-label">Date:</span> {!! $d($sheet?->endorsement_date) !!}</div>
        </td>
        <td width="50%">
            <div class="signature-box">Sign inside the box (print &amp; sign)</div>
            <div style="text-align: center; font-size: 9px; margin-bottom: 6px;">Director's Signature</div>
            <div class="field-row"><span class="field-label">Date of Approval:</span> {!! $d($sheet?->director_approval_date) !!}</div>
        </td>
    </tr>
</table>
