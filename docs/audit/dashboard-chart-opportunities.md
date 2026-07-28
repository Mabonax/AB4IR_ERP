# Dashboard Chart Opportunities Audit

This audit reviews the current Programme of Action ERP dashboard surfaces and identifies where charts would improve manager decision-making, especially around beneficiary decline, milestone completion, attendance health, and workflow pressure.

## Current state

- The repo already has multiple dashboard surfaces with meaningful summary metrics.
- Most dashboards currently stop at KPI cards, tables, and intervention lists.
- There is no charting library currently installed in `package.json`.
- The highest-value chart rollout should start where the backend already exposes meaningful operational data:
  1. `Projects`
  2. `ProjectLocations`
  3. `Beneficiaries`
  4. `BusinessDevelopment`
  5. `TaskManagement`

## Important implementation rule

Not every chart should be built from current point-in-time totals.

- Current-state charts are safe when the question is distribution, composition, or ranking.
- Trend charts must use date-bucketed data, not current totals replayed as fake history.
- Beneficiary decline, milestone trend, attendance trend, and approval trend should come from real snapshots or dated transactional records.

## Priority 1: Projects and beneficiary delivery

These are the best first charts because the repo already contains strong delivery metrics and manager workflows.

### `resources/js/pages/Projects/Dashboard.tsx`

#### Recommended charts

- Bar chart: `Milestone completion rate by project`
  - Shows which projects are behind on milestone delivery.
- Bar chart: `Beneficiary completion rate by project`
  - Shows where delivery is not translating into actual beneficiary progression.
- Bar chart: `Blocked locations by project`
  - Gives managers an immediate escalation view.
- Line chart: `Portfolio average attendance rate over time`
  - Requires date-bucketed attendance history.
- Line chart: `Portfolio average milestone completion trend`
  - Requires milestone completion snapshots or dated aggregation.

#### Why it matters

- The page already answers “what is happening now.”
- Charts would answer “where is the biggest problem” and “is it improving or declining.”

#### Data readiness

- Immediate: per-project completion and blocked-site bar charts.
- Needs backend shaping: line trends over time.

### `resources/js/pages/Projects/Show.tsx`

#### Recommended charts

- Bar chart: `Location vs milestone completion`
  - One bar per site.
- Bar chart: `Location vs beneficiary completion`
  - Highlights which site is dragging project outcomes down.
- Line chart: `Attendance capture trend for this project`
  - Bucket by attendance register date.
- Stacked bar chart: `Beneficiary movement`
  - Active vs completed vs dropped.
- Progress timeline chart: `Milestone completion over project life`
  - Requires dated milestone completion aggregation.

#### Why it matters

- This page already has manager-grade metrics and blockers.
- Charts would let a project manager see delivery imbalance between locations much faster than reading cards and lists.

#### Data readiness

- Immediate: location comparison bars, beneficiary movement stack.
- Needs backend shaping: milestone and attendance trends over time.

### `resources/js/pages/ProjectLocations/Dashboard.tsx`

#### Recommended charts

- Bar chart: `Completed assessments by location`
- Bar chart: `Assessment completion percentage by location`
- Scatter or ranked bar chart: `Beneficiaries vs completed assessments`
- Line chart: `Attendance trend by location`
  - Requires register-date aggregation.

#### Why it matters

- Facilitators and delivery managers need a rapid site-comparison view.
- This is one of the clearest places to visualize underperforming locations.

### Beneficiary surfaces

There is no dedicated beneficiary dashboard page yet, but the beneficiary domain has enough operational importance to justify one.

#### Recommended future dashboard

- `Beneficiaries Dashboard`

#### Recommended charts

- Line chart: `Beneficiary active population over time`
- Line chart: `Dropout/decline trend over time`
- Bar chart: `Beneficiaries by project`
- Bar chart: `Beneficiaries by attendance status`
- Stacked bar chart: `Enrollment status by project`
- Cohort line chart: `Retention by intake/cohort month`

#### Why it matters

- The user specifically called out beneficiary decline.
- That is not best shown on an individual beneficiary file; it needs cohort-level and project-level trend views.

#### Data readiness

- Immediate: by-project and by-status distribution charts.
- Needs backend shaping: decline/retention trend charts.

## Priority 2: Business Development

### `resources/js/pages/BusinessDevelopment/Dashboard.tsx`

#### Recommended charts

- Funnel chart: `Applications -> assessed -> accepted -> pitch scheduled -> incubated`
- Bar chart: `Application outcome by month`
- Line chart: `Application intake trend`
- Line chart: `Incubatee active/inactive trend`
- Bar chart: `Pending applications by stage`

#### Why it matters

- This domain is workflow-heavy and stage-based.
- Visual flow is much stronger than isolated counts here.

#### Data readiness

- Immediate: funnel and status-distribution charts.
- Needs backend shaping: monthly intake and conversion trend series.

## Priority 3: Task Management and support pressure

### `resources/js/pages/TaskManagement/Dashboard.tsx`

#### Recommended charts

- Stacked bar chart: `Tasks by status`
- Bar chart: `Overdue tasks by department`
- Bar chart: `Active workload by assignee`
- Line chart: `Ticket SLA overdue trend`
- Bar chart: `Project-linked support pressure`
- Stacked bar chart: `Support tickets by status`

#### Why it matters

- Managers need to see queue pressure and operational bottlenecks, not just lists.
- This dashboard already contains the right categories for charting.

#### Data readiness

- Immediate: status distribution, workload ranking, department pressure.
- Needs backend shaping: SLA trend over time.

## Priority 4: Marketing

### `resources/js/pages/Marketing/Dashboard.tsx`

#### Recommended charts

- Bar chart: `Workload by assignee`
- Bar chart: `Workload by unit`
- Donut or bar chart: `Deliverables by type`
- Line chart: `Reach / impressions / engagements over time`
- Bar chart: `Top campaigns by reach`
- Line chart: `Publication activity by week`

#### Why it matters

- The dashboard already separates operations from performance.
- This is ideal for mixing operational bar charts with performance trend lines.

#### Data readiness

- Immediate: workload and type distribution.
- Immediate if stored historically: campaign and publication performance trends.

## Priority 5: HR, Staff, and Leave

### `resources/js/pages/HumanResources/Dashboard.tsx`

#### Recommended charts

- Bar chart: `Staff count by department`
- Stacked bar chart: `Leave approvals by stage`
- Bar chart: `Annual vs sick leave usage by department`
- Line chart: `Leave approval volume over time`
- Heatmap: `Departments with the highest pending leave pressure`

#### Why it matters

- The page already mixes staff and leave operations.
- Charts would turn it into an actual workforce monitoring board.

#### Data readiness

- Immediate: staff count by department, approval stage distribution.
- Needs backend shaping: leave trend over time by department.

### `resources/js/pages/Staff/Dashboard.tsx`

#### Recommended charts

- Bar chart: `Leave balance by direct report`
- Bar chart: `Pending approvals by manager/team`
- Stacked bar chart: `Annual vs sick leave exposure for the team`

#### Why it matters

- This is a manager-facing people view and should visualize team leave risk more clearly.

## Priority 6: Assets

### `resources/js/pages/Assets/Dashboard.tsx`

#### Recommended charts

- Donut chart: `Asset state distribution`
  - Assigned, unassigned, maintenance, retired.
- Bar chart: `Serial compliance status`
  - Recorded vs pending vs no serial.
- Line chart: `Asset growth / retirement trend`
  - Requires dated acquisition and retirement buckets.

### `resources/js/pages/Assets/ManagerDashboard.tsx`

#### Recommended charts

- Bar chart: `Assets by staff member`
- Bar chart: `Maintenance volume by category or model`
- Line chart: `Assignment and return activity over time`
- Bar chart: `Assets tied to projects`

#### Why it matters

- This would help department managers spot concentration risk and maintenance pressure.

## Priority 7: Finance

### `resources/js/pages/Finance/TravelClaims/Index.tsx`

#### Recommended charts

- Stacked bar chart: `Claims by approval stage`
- Line chart: `Claim submission trend by month`
- Bar chart: `Claim amount by department`
- Line chart: `Paid vs pending value over time`

#### Why it matters

- Finance managers usually care about queue stage, volume, and money over time.

## Priority 8: Home dashboard

### `resources/js/pages/dashboard.tsx`

#### Recommended charts

- Small sparkline or mini bar chart inside each secondary widget
  - Projects: blocked sites or active projects trend
  - Leave: pending approvals trend
  - Marketing: pending approvals trend
  - Assets: maintenance trend
  - Staff: pending approvals or team capacity trend
- Compact stacked bar: `My visible tasks by status`
- Compact stacked bar: `My visible tickets by status`

#### Why it matters

- The home dashboard should stay lightweight.
- It should use small trend visuals, not large analytical charts.

## Recommended rollout order

### Phase 1: immediate value, low backend risk

- Projects dashboard
- Project show page
- Project locations dashboard
- Task management dashboard
- Marketing dashboard workload charts
- HR department/staff distribution charts

### Phase 2: high-value trend work

- Beneficiary decline and retention trends
- Attendance trend lines
- Milestone completion trend lines
- Business development intake/conversion trends
- Leave trend analysis
- Asset movement trends

### Phase 3: executive polish

- Home dashboard mini-trends
- Cross-domain executive summary boards
- Shared chart filters by date range, project, department, and program

## Implementation guidance

### Chart types that fit this repo best

- Bar charts for ranked comparisons across projects, locations, departments, assignees, and campaigns.
- Line charts for time-based trends like attendance, decline, approvals, claim volume, and ticket pressure.
- Stacked bar charts for status composition such as active/completed/dropped or open/in-progress/completed.
- Donut charts only for simple composition where exact ranking is not the main question.
- Avoid pie-heavy dashboards.

### UI guidance

- Keep charts close to the decision they support.
- Do not place charts above every KPI card just because charts are available.
- Use charts to answer:
  - What is declining?
  - What is blocked?
  - Which project/site/team is worst?
  - Is the situation improving over time?

### Backend guidance

- Add reusable date-bucket query helpers per domain instead of building one-off chart SQL inside controllers.
- For beneficiary decline and milestone trends, prefer materialized daily or weekly snapshots if live joins become expensive.
- Preserve domain boundaries:
  - `Projects` owns project delivery trends.
  - `Beneficiaries` owns cohort and decline views.
  - `TaskManagement` owns workload and SLA trend views.
  - `Marketing` owns campaign/performance trends.

## Best first charts to build next

If implementation starts immediately, these are the strongest first six:

1. `Projects Dashboard` bar chart for milestone completion by project.
2. `Projects Dashboard` bar chart for beneficiary completion by project.
3. `Project View` bar chart for location-by-location milestone completion.
4. `Project View` stacked bar for total vs completed vs dropped beneficiaries.
5. `ProjectLocations Dashboard` bar chart for assessment completion by location.
6. `Beneficiaries Dashboard` line chart for beneficiary decline over time.

The sixth item is strategically important, but it likely needs a proper beneficiary trend endpoint before the UI chart is honest.
