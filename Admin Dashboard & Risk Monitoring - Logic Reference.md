# Admin Dashboard & Risk Monitoring — Logic Reference

This document explains exactly how every number, chart, and colored pill on the admin **Dashboard** and **Risk Monitoring** pages is computed, so testing can distinguish "working as designed" from an actual bug. A few pieces of this logic were invented for this build because no official spec covered them — those are called out explicitly below so they can be revisited with the actual PUP-TBIDO rubric owners if the computed thresholds don't feel right.

All Dashboard numbers respect the cohort filter in the top-right dropdown ("All Cohort" or a specific cohort). Selecting a cohort re-scopes every stat, chart, and list to only that cohort's startups.

## 1. The Risk Engine (shared by both pages)

Both the Dashboard's "At Risk Startup" stat, its "Risk Classification" donut, and the entire Risk Monitoring page are driven by the same underlying scoring engine (`app/Support/RiskEngine.php`). Understanding this once explains the colored pills on both pages.

For every startup, the engine checks nine independent conditions ("indicators"). Any condition that is currently true for that startup is "triggered" and contributes points to that startup's Total Risk Score. A startup with none triggered scores 0.

| Indicator | Category | Severity | Base score | When it triggers |
|---|---|---|---|---|
| No Information Sheet | Information Sheet | Critical | 5 | The startup has no Information Sheet row at all — hasn't even saved a Startup Profile yet. |
| Incomplete Information Sheet | Information Sheet | High | 4 | A row exists but the founder has never actually gone through the real Information Sheet submission (see "Incomplete vs Not Evaluated" below). |
| Information Sheet Not Evaluated | Information Sheet | High | 4 | The sheet was genuinely submitted (has a `submission_date`) but its `approval_status` is not yet "Approved". |
| No Mentor Assigned to Submitted Roadblock | Mentor Coordination | Medium | 3 | The startup has a Roadblock still sitting in "Pending" status with no mentor assigned. |
| No Portfolio Coordinator Assigned | Portfolio Coordinator | Low | 1 | Only checked once the startup is approved, and there is no `CoordinatorAssignment` with `assignment_status = 'Active'`. |
| Failed Mentorship | Mentor Coordination | High | 4 | The startup has at least one Roadblock with `status = 'Failed'`. This one is flat — see below. |
| Pre-Assessment Overdue | Readiness Assessment | High | 4 | The startup's cohort has a start date, it's been 2+ months since that date, and the startup has no scored Pre-Assessment yet. |
| Active-Assessment Overdue | Readiness Assessment | High | 4 | 4+ months since cohort start, and the startup is missing at least one of Assessment Documents 6, 7, or 8. |
| Post-Assessment Overdue | Readiness Assessment | Critical | 5 | 5+ months since cohort start, and the startup has no scored Post-Assessment yet. |
| Venture Exit Overdue | Readiness Assessment | Medium | 3 | 5+ months since cohort start, and the startup hasn't submitted the Venture Exit form (Document 13). |

**Failed Mentorship is an addition beyond the original 5-indicator spec I was given** — it's a reasonable 6th condition (a startup whose mentorship attempt outright failed is clearly at risk), but it should be confirmed as in-scope, not assumed.

**The four Readiness Assessment indicators are new** and their severities/base scores (High/4, High/4, Critical/5, Medium/3) are my own judgment call, not an explicit spec — worth confirming they feel right, especially whether Post-Assessment Overdue should really outrank everything else at Critical.

**"No Weekly Updates" has been retired** — per direct testing feedback, it's no longer tracked at all: not scored, not shown as a category or badge anywhere.

### Incomplete vs Not Evaluated

An `InformationSheet` row can exist without the founder ever having gone through the actual Information Sheet form — the Startup Profile page also creates/touches this same row (setting only `business_description`) the moment a founder saves their profile, well before they've filled in personal details or hit submit on the real Information Sheet. The one reliable signal that the real form was actually submitted is `submission_date`, a column that is only ever set inside the Information Sheet's own `update()` action — never by the Profile save. So:

- **Incomplete Information Sheet** = a row exists, but `submission_date` is still null (they've touched the Profile page at most, never the actual Information Sheet form).
- **Information Sheet Not Evaluated** = `submission_date` is set (they genuinely submitted the form) but it isn't `Approved` yet.

These two are mutually exclusive by construction and their severities (both High/4) are my judgment call, worth confirming.

### Why the Readiness Assessment indicators are measured differently

Every other indicator anchors to a timestamp already stored on the startup itself (when it was created, when its sheet was submitted, when a roadblock came in). Pre-Assessment, Active-Assessment, Post-Assessment, and Venture Exit have no such per-startup deadline anywhere in the data — the incubation program doesn't record "this startup must finish Active-Assessment by X." Per the incubation team, these four instead run on a fixed calendar relative to the startup's **cohort start date**:

- Pre-Assessment is due 2 months after cohort start.
- Active-Assessment is due 4 months after cohort start.
- Post-Assessment and Venture Exit are both due 5 months after cohort start.

A startup only gets flagged once its cohort's due date has actually passed, and the flag's escalation (see below) is measured from that due date rather than from a creation timestamp. If a startup's cohort has no `start_date` set, none of these four indicators can trigger for it — there's no due date to measure against.

### Time-based escalation

Every indicator except "Failed Mentorship" gets an extra point added on top of its base score the longer the underlying problem has gone unaddressed, so a startup that's been missing an Information Sheet for two weeks scores higher than one that's been missing it for one day.

- **Day-based indicators** (No Information Sheet, Incomplete Information Sheet, Information Sheet Not Evaluated, No Mentor Assigned, No Portfolio Coordinator, and all four Readiness Assessment indicators — measured from their due date instead of a creation date): 1–3 days late = +1, 4–7 days = +2, 8+ days = +3.
- **Failed Mentorship** does not escalate — a "Failed" status is a one-time terminal outcome, not an ongoing delay, so it always contributes exactly its flat base score of 4.

A triggered indicator's final score is `base_score + escalation`. For example, "No Mentor Assigned" sitting unresolved for 10 days scores `3 + 3 = 6`.

### Total score → overall risk level

A startup's **Total Risk Score** is the sum of every currently-triggered indicator's score. That total is bucketed into the overall level shown as the colored pill:

| Total score | Level | Color |
|---|---|---|
| 15+ | Critical | `#B91C1C` (dark red) |
| 10–14 | High | `#EA580C` (orange) |
| 5–9 | Moderate | `#D97706` (amber) |
| 1–4 | Low | `#059669` (green) |
| 0 | None | `#9CA3AF` (gray) |

So a startup showing a red "Critical" pill has at least 15 combined points across its triggered indicators — for instance, a missing Information Sheet that's been ignored for 8+ days (5 + 3 = 8) plus a failed mentorship (4) plus no coordinator for a week (1 + 2 = 3) adds up to 15. Individual indicator badges (shown inside a startup's detail view) are colored by that specific indicator's own severity (Critical/High/Medium/Low), which is a separate, fixed lookup — not derived from the total.

## 2. Risk Monitoring page

- **Risk Classification donut** (renamed from "Risk Register" for consistency with the Dashboard's naming): counts every startup by overall level (Critical/High/Moderate/Low/None) and draws one ring segment per level that actually has startups in it, sized proportionally, with a small gap between segments for readability. Zero-count levels don't get a segment at all.
- **Top Risk Categories table**: for each of the four categories (Information Sheet, Portfolio Coordinator, Mentor Coordination, Readiness Assessment), counts how many startups have *at least one* triggered indicator in that category, then breaks those startups down by their *overall* level — so this shows how serious the fallout tends to be for startups affected by that category, not the category's own severity.
- **Risk Indicator table**: one row per startup that has a Total Risk Score greater than 0, sorted highest score first. Startups with a score of 0 (level "None") are omitted entirely, since there's nothing actionable to show for them. Clicking "View" opens the full breakdown of every triggered indicator for that startup with its base/escalation/final score.
- **Clickable indicator badges**: every indicator badge — in the table and inside the "View" detail modal — is now a link straight to wherever an admin would actually resolve that specific problem, instead of just describing it. Information Sheet indicators go to that startup's Information Sheet page; No Mentor Assigned / Failed Mentorship go to Roadblock Management (Manage or Archive → Failed) with that startup's row flashed briefly so it's easy to spot; No Portfolio Coordinator goes to that startup's profile page with the Portfolio Coordinator section flashed; the four Readiness Assessment indicators go straight to that startup's specific stage inside Assessment Hub's Assessment tab.

## 3. Admin Dashboard

### Stat cards

- **Total Startup**: a plain count of startups in scope (respecting the cohort filter).
- **Assessed Startup**: count of *distinct* startups with at least one `ReadinessLevelAssessment` (Pre or Post) that has a non-null `overall_score`. The "Pre RL's X · Post RL's Y" subtext counts Pre-Assessment and Post-Assessment rows separately (a startup with both counts toward both X and Y).
- **At Risk Startup**: count of startups whose Risk Engine Total Score is greater than 0 (i.e., level is anything other than "None"). The percentage shown is `at-risk count ÷ total startups`.
- **Intervention Provided**: count of Roadblocks with `status = 'Resolved'` whose `resolved_at` falls within the current calendar month.
- **Sparklines**: Total Startup, Assessed Startup, and Intervention Provided draw real weekly counts for the last 6 weeks (new startups created, new scored assessments recorded, roadblocks resolved, respectively) grouped by calendar week. **At Risk Startup's sparkline is decorative, not real data** — risk is a current-state calculation with no historical snapshot stored anywhere in the database, so there's nothing genuine to trend against.

### Incubation Progress donut

This is **not** the official PUP-TBIDO 0–9 readiness scale — it's a 0–5 scale invented specifically for this card because the reference mockup called for 5 buckets. For each startup with a scored assessment, its `overall_score` (0–9) is rescaled with `score ÷ 9 × 5`, preferring the Post-Assessment score if one exists, otherwise falling back to Pre-Assessment. The rescaled value is then bucketed:

| Bucket | Range | Color |
|---|---|---|
| High Ready | 4.21 – 5.00 | Green |
| Moderately Ready | 3.41 – 4.20 | Olive |
| Moderately Unready | 2.61 – 3.40 | Amber |
| Not Ready | 1.81 – 2.60 | Orange |
| Critically Unready | 1.00 – 1.80 | Red |

Only startups with a scored assessment count toward this donut's total — unassessed startups aren't included, which is why this total is usually smaller than "Total Startup."

### Risk Classification donut

Identical logic and colors to the Risk Classification donut on the Risk Monitoring page, just re-scoped to the currently filtered cohort and re-labelled "Total Startups" instead of counting only at-risk ones.

### Average Readiness Level

Averages every in-scope startup's TRL, MRL, TMRL, and SRL scores for whichever stage is selected (Pre-Assessment or Post-Assessment, toggled via the dropdown in the card header) — startups with no score for a given category simply don't count toward that category's average. The radar chart and the four boxes both plot these averages. The footer line's "Overall" score/label reuses the same 0–9 scale as the founder-side Readiness Result page (Ideation < 3, Development < 6, Validation < 8, Growth ≥ 8).

### Milestone Completion

Eight milestones **invented for this card** — there's no existing canonical milestone list anywhere else in the app, so this list and its formulas should be sanity-checked against what the incubation team actually considers meaningful progress. Each is expressed as the percentage of in-scope startups that have reached it:

| Milestone | Counts a startup as "done" when… |
|---|---|
| Profile Setup | Its `industry_sector`, `location`, and `contact_phone` fields are all filled in. |
| Information Sheet | It has an Information Sheet record at all (regardless of approval status). |
| Assign Profile Coordinator | It has a `CoordinatorAssignment` with `assignment_status = 'Active'`. |
| Pre-Assessment | It has a Pre-Assessment `ReadinessLevelAssessment` with a non-null `overall_score`. |
| Assign Mentor | It has at least one Roadblock with a mentor assigned. |
| Active-Assessment | It has all three Active-Assessment documents on file (Document 6, 7, and 8). |
| Post-Assessment | It has a Post-Assessment `ReadinessLevelAssessment` with a non-null `overall_score`. |
| Venture Exit | It has submitted the Venture Exit form (Document 13). |

"Overall Completion Rate" is the plain average of these eight percentages — it is not weighted by how many startups exist in each stage.

## 4. Known simplifications worth flagging to the tester

- The Incubation Progress buckets, the Milestone Completion list, and "Failed Mentorship" as a risk indicator were all invented to fill gaps in the reference material — they're internally consistent and reasonable, but not derived from an official written spec.
- The At Risk Startup stat card's sparkline is intentionally decorative rather than real data, for the reason explained above.
- "Assessed Startup" counts a startup once per stage it's been assessed in, so a fully-assessed startup (both Pre and Post) contributes to both the Pre RL and Post RL sub-counts shown in the card.
- The four Readiness Assessment risk indicators (Pre/Active/Post-Assessment + Venture Exit overdue) use cohort-relative due dates (2/4/5/5 months after cohort start) confirmed directly by the incubation team, but my chosen severities/base scores for those four are still a judgment call worth double-checking.
- A startup whose cohort has no `start_date` set will never show any of the four Readiness Assessment risk indicators, since there's no due date to measure against.
- "Incomplete Information Sheet" is a new indicator that uses `submission_date` as the "did they actually submit the real form" signal, chosen because it's the one existing column that's set only by the real Information Sheet submission and never by the separate Startup Profile save — this was a code-level judgment call (not an explicit spec) worth confirming matches the incubation team's intent.
