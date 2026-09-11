# Risk Monitoring — How It Works

This document explains the admin **Risk Monitoring** module: what it measures, exactly how a startup's risk score is computed, and where every piece of it lives in the codebase.

- Page: `/admin/risk-monitoring` (route name `admin.risk-monitoring.index`)
- Access: `auth` + `role:Admin` + `select-cohort` middleware
- Controller: `app/Http/Controllers/Admin/RiskMonitoringController.php`
- Scoring engine: `app/Support/RiskEngine.php`
- View: `resources/views/admin/risk-monitoring/index.blade.php`
- Tests: `tests/Feature/Admin/RiskMonitoringTest.php`

## 1. What it is

Risk Monitoring flags startups whose incubation paperwork or milestones have stalled — a missing Information Sheet, an unassigned mentor, an overdue assessment, and so on. It is **not** related to a startup's TRL/MRL/TMRL/SRL readiness rubric scores (that's a separate system, see `app/Support/ReadinessRubric.php`). A startup can have a great readiness score and still show up here if it's missing a coordinator, and vice versa — risk is about missing or stalled *process* artifacts and elapsed time, not the quality of the startup's progress.

## 2. The core idea

Every startup is checked against a fixed list of **7 risk indicators**. Each indicator either triggers (the underlying problem exists right now) or doesn't. Every triggered indicator contributes a score:

```
indicator score = base_score + time_escalation
```

A startup's **Total Risk Score** is the sum of every triggered indicator's score. That total is then classified into an overall risk level (None / Low / Moderate / High / Critical).

All of this happens in one function: `RiskEngine::assess(Startup $startup, ?Collection $documents)`, called once per startup by `RiskMonitoringController::index()`. `$documents` is that startup's full `AssessmentDocument` collection (needed to check Active-Assessment doc completion and the Venture Exit form).

> **Note:** The Information Sheet itself is no longer part of this computation. It used to contribute 3 indicators (No Information Sheet, Incomplete Information Sheet, Information Sheet Not Evaluated), but the sheet is now assessed earlier in the flow — via the Assessment Hub's Evaluation Schedule and Approve/Reject workflow — before a startup is ever admitted into a cohort, so a startup risk-scored here has already been evaluated one way or another. Removing those 3 indicators also retired the `Information Sheet` risk category entirely. The classification thresholds in section 6 were left unchanged (they read as fixed severity cutoffs, not a percentage of the maximum possible score).

## 3. The 7 risk indicators

| # | Indicator key | Label | Category | Severity | Base Score |
|---|---|---|---|---|---|
| 1 | `no_mentor_assigned` | No Mentor Assigned to Submitted Roadblock | Mentor Coordination | Medium | 3 |
| 2 | `no_portfolio_coordinator` | No Portfolio Coordinator Assigned | Portfolio Coordinator | Low | 1 |
| 3 | `failed_mentorship` | Failed Mentorship | Mentor Coordination | High | 4 |
| 4 | `no_pre_assessment` | Pre-Assessment Overdue | Readiness Assessment | High | 4 |
| 5 | `no_active_assessment` | Active-Assessment Overdue | Readiness Assessment | High | 4 |
| 6 | `no_post_assessment` | Post-Assessment Overdue | Readiness Assessment | Critical | 5 |
| 7 | `no_venture_exit` | Venture Exit Overdue | Readiness Assessment | Medium | 3 |

### What triggers each one

**1. No Mentor Assigned to Submitted Roadblock** — the startup has a Roadblock with `status = 'Pending'` and no `mentor_id`. If there are several, the *oldest* one is used, so the score reflects the longest-ignored case. Clock starts at that roadblock's `created_at`.

**2. No Portfolio Coordinator Assigned** — only checked once the Information Sheet is `Approved` (otherwise every not-yet-accepted applicant would spuriously show this from day one). Triggers if the startup has no active `CoordinatorAssignment`. There's no dedicated "approved at" timestamp on the sheet, so the sheet's `updated_at` (falling back to the startup's `created_at`) is used as the best available proxy for "since when has this needed a coordinator."

**3. Failed Mentorship** — the startup has any Roadblock with `status = 'Failed'`. This one is flat: no time escalation is added, because a "Failed" status is a discrete terminal outcome, not an ongoing delay that gets worse the longer it sits. Its score is always exactly 4.

**4–7. Pre-Assessment / Active-Assessment / Post-Assessment / Venture Exit Overdue** — see section 5 below; these four work differently from the rest.

## 4. Time-based escalation ("day tiers")

Most indicators (everything except Failed Mentorship) add extra points the longer the problem has existed, on top of the base score:

| Days since the anchor date | Extra points |
|---|---|
| 0 days | +0 |
| 1–3 days | +1 |
| 4–7 days | +2 |
| 8+ days | +3 |

So, for example, a startup with a Pending, unassigned Roadblock for 10 days scores `3 (base) + 3 (8+ days) = 6` for that indicator alone.

## 5. The four assessment/exit indicators work on a different clock

Pre-Assessment, Active-Assessment, Post-Assessment, and Venture Exit have no stored "due date" anywhere in the data model — unlike the other indicators, which anchor to a real timestamp that already exists (startup creation, sheet submission, roadblock creation). Instead, they're measured against the startup's **cohort's** start date:

| Indicator | Due (months after cohort start) |
|---|---|
| Pre-Assessment Overdue | 2 |
| Active-Assessment Overdue | 4 |
| Post-Assessment Overdue | 5 |
| Venture Exit Overdue | 5 |

- If the startup's cohort has no `start_date` set, none of these four indicators can ever trigger for that startup — there's nothing to measure against.
- If the due date hasn't arrived yet, the indicator doesn't trigger at all (not even at 0 points).
- Once the due date has passed, the same day-tier table from section 4 applies, counting from the due date instead of the original trigger event.

Trigger conditions once a due date has passed:

- **Pre-Assessment Overdue** — no `ReadinessLevelAssessment` row with `stage = 'Pre-Assessment'` and a non-null `overall_score`.
- **Active-Assessment Overdue** — fewer than 3 distinct `AssessmentDocument` rows for `stage = 'Active-Assessment'` with `document_number` in `[6, 7, 8]`.
- **Post-Assessment Overdue** — no `ReadinessLevelAssessment` row with `stage = 'Post-Assessment'` and a non-null `overall_score`.
- **Venture Exit Overdue** — no `AssessmentDocument` matching the Venture Exit form's document number.

Severity climbs the closer to graduation the missed stage is (Pre-Assessment is High, Post-Assessment is Critical), since a startup running out of runway near the end of the program has less time left to recover.

## 6. Total score and risk level

```
Total Risk Score = sum of (base_score + time_escalation) for every currently-triggered indicator
```

| Total Score | Risk Level |
|---|---|
| 0 | None |
| 1 – 4 | Low |
| 5 – 9 | Moderate |
| 10 – 14 | High |
| 15+ | Critical |

Colors used throughout the UI:

| Level | Color |
|---|---|
| Critical | `#FF2525` (red) |
| High | `#FF9B20` (orange) |
| Moderate | `#F2BE25` (yellow) |
| Low | `#00BF1D` (green) |
| None | `#9CA3AF` (gray) |

Note on wording: an overall *level* is called "Moderate," while an individual indicator's *severity* uses "Medium" for that same color tier. Same color, deliberately different label — don't conflate the two when writing about this feature.

## 7. Risk categories

Every indicator belongs to exactly one of three categories, used for the page's "Top Risk Categories" breakdown:

- **Portfolio Coordinator** — indicator 2
- **Mentor Coordination** — indicators 1, 3
- **Readiness Assessment** — indicators 4, 5, 6, 7

## 8. What the page actually shows

`RiskMonitoringController::index()` reads the app-wide selected cohort from `session('selected_cohort_id')` (set via the sidebar cohort control). If a cohort is selected, only that cohort's startups are assessed; otherwise every startup is. It runs `RiskEngine::assess()` once per startup (eager-loading `informationSheet`, `activeCoordinatorAssignment`, `roadblocks`, `readinessAssessments`, `cohort`, plus all `AssessmentDocument` rows grouped by startup) and reuses that one result set for everything on the page:

1. **Risk Classification** — a donut chart of how many startups fall into each level (Critical / High / Moderate / Low / None), plus counts and percentages. Center of the donut shows the total number of startups (in the current cohort filter).
2. **Top Risk Categories** — for each of the 3 categories, how many startups have at least one triggered indicator in it, further broken down by those startups' *overall* level (not the indicator's own severity).
3. **Risk Indicator table** — every startup with a Total Risk Score greater than 0, sorted highest score first. Each row shows the startup, its overall risk level (colored pill), its numeric score, and a clickable chip per triggered indicator (colored by that indicator's own severity). Clicking a chip or the row's "View" button opens a modal listing every triggered indicator for that startup with its score and severity.

Startups with a score of 0 (no triggered indicators) don't appear in the Risk Indicator table, but they are counted in the "None" bucket of the Risk Classification donut.

### Where each indicator sends the admin to fix it

Clicking an indicator chip routes straight to wherever that problem gets resolved:

| Indicator(s) | Links to |
|---|---|
| No Mentor Assigned | Roadblock Management, Manage tab, that startup's row highlighted |
| Failed Mentorship | Roadblock Management, Archive → Failed tab, that startup's row highlighted |
| No Portfolio Coordinator | That startup's profile page, Portfolio Coordinator section highlighted |
| Pre/Active/Post-Assessment Overdue, Venture Exit Overdue | Assessment Hub, Assessment tab, scoped to that startup + stage |

## 9. The sidebar "new risk" indicator

Independent of any cohort filter, the controller also computes a signature over *every* startup's triggered indicators (an md5 hash of every `startup_id:indicator_key` pair, sorted) and caches it forever as `risk_monitoring_signature`. Each admin user has their own `risk_monitoring_seen_signature` column; when the two don't match, a red dot appears on the Risk Monitoring sidebar item, computed in `AppServiceProvider::boot()`. Visiting the page updates the user's seen signature. This is purely a "something changed since you last looked" cue — it has no effect on scores or levels.

## 10. Reused elsewhere: the admin Dashboard

`RiskEngine::assess()` also powers two Dashboard cards (`DashboardController.php`):

- **"At Risk Startup" stat card** — count of startups with `score > 0`. Its percentage is against the *total* startup pool, not just the assessed ones, and its sparkline is purely decorative (risk has no historical snapshot to trend against).
- **Risk Classification card** — the same level/color breakdown as Risk Monitoring's donut, scoped to the Dashboard's own startup set, but ordered ascending (`None → Critical`) instead of Risk Monitoring's descending order.

## 11. Quick reference: file map

| Concern | File |
|---|---|
| Route | `routes/web.php` (`admin.risk-monitoring.index`) |
| Controller / page data | `app/Http/Controllers/Admin/RiskMonitoringController.php` |
| Scoring engine (indicators, thresholds, formulas) | `app/Support/RiskEngine.php` |
| View | `resources/views/admin/risk-monitoring/index.blade.php` |
| Tests | `tests/Feature/Admin/RiskMonitoringTest.php` |
| Sidebar "new risk" badge | `app/Providers/AppServiceProvider.php` (`View::composer('components.layouts.admin', ...)`) |
| Dashboard reuse | `app/Http/Controllers/Admin/DashboardController.php` (`buildStatCards()`, `buildRiskClassification()`) |
| Unrelated readiness rubric (TRL/MRL/TMRL/SRL) — not part of risk scoring | `app/Support/ReadinessRubric.php` |

To change a threshold, score, or add a new indicator, everything lives as class constants (`INDICATORS`, `ASSESSMENT_DUE_MONTHS`, `LEVEL_COLORS`, `SEVERITY_COLORS`) and static methods (`classify()`, `assess()`, `dayTierScore()`, `assessmentDueScore()`) directly in `RiskEngine` — there's no database table or config file backing any of this.
