# Execution Backlog

This backlog is ordered to reduce architectural risk before domain expansion.

## Phase 1: Cross-Cutting Foundation

Goal: restore a trustworthy engineering baseline.

### 1.1 Test Baseline Recovery

- Replace starter auth/settings expectations with permission-aware fixtures.
- Add role and permission helpers for tests so domain scenarios are not forced to hand-roll access setup.
- Make profile, password, and two-factor tests reflect the current gated route model.

Expected outcome:

- `php artisan test` becomes meaningful again as a platform signal.

Status:

- Completed. The PHP suite now passes with permission-aware fixtures and root-route expectations aligned to the current application behavior.

### 1.2 Authorization Pattern Standardization

- Define one repeatable rule for each domain:
  - route middleware for coarse access,
  - policy for record/state transitions,
  - service-level authorization for high-risk workflows.
- Extend policy coverage beyond adjudication.

Expected outcome:

- transaction policies become explicit and reusable.

Status:

- Started. `ProjectPolicy` and `BeneficiaryPolicy` now use a shared domain-permission helper pattern, are registered centrally, and are enforced in their controllers.

### 1.3 Frontend Type Baseline

- Repair `CustomModelForm` typing around route definitions and submit options.
- Repair `CustomTable` icon and action typing.
- Align field config typing with actual page usage patterns.

Expected outcome:

- `npm run types` becomes a real regression gate instead of permanent noise.

### 1.4 Audit Documentation

- Keep this audit pack updated as each domain is hardened.
- Add state diagrams and transaction notes as workflows are clarified.

## Phase 2: Projects + Beneficiaries

Goal: harden the operational core.

### 2.1 Project Workflow Audit

- map project lifecycle states,
- document milestone sync rules,
- document attendance permissions and holiday authority,
- identify where policies should replace controller checks.

### 2.2 Beneficiary Lifecycle Audit

- define beneficiary creation, enrollment, transfer, dropout, and archival rules,
- clarify whether beneficiary identity is project-bound or platform-wide,
- add transaction rules for enrollment changes.

Status:

- Started. Beneficiary writes now enforce project-location alignment at the service layer, transfering a beneficiary to a new project now drops the prior active enrollment, and dropout state maps to dropped enrollment state.

### 2.3 Policy and Transaction Pass

- add formal policies for project-sensitive actions,
- move remaining orchestration from controllers into services,
- add regression tests around enrollment and attendance edge cases.

Status:

- In progress. Policies are in place for `Projects` and `Beneficiaries`, and service-level enrollment consistency rules now backstop both beneficiary mutations and direct project enrollment writes.

## Phase 3: Business Development

Goal: use the strongest domain as the policy-quality template.

### 3.1 Application Workflow

- formalize state machine for pending, accepted, rejected, pitched, adjudicated, incubated,
- review who can assess, schedule, unlock, and convert,
- centralize any remaining inline role checks.

Status:

- Started. `BdsApplication` now has an explicit policy, service-level workflow guards for reassessment and pitch scheduling, future-only pitch validation, and UI-visible workflow blockers/readiness on the application list and detail screens.
- Panel-session groundwork is now in place through pitch sessions, panel members, listed prospects, per-session adjudication linkage, score consolidation, and manager-approved final incubation decisions.
- Pitch-session web workflow is now live through dedicated routes, a policy-backed controller, session resources, list/detail Inertia screens, route helpers, and direct panel-day progression actions for start, consolidate, and manager approval.
- Adjudication draft creation can now be launched in session context so panelists can open scorecards directly from the assigned pitch-session workflow.

### 3.2 Incubatee Lifecycle

- define activation, inactivity, progress, and exit rules,
- clarify which transitions are reversible and which are not.

### 3.3 Test Expansion

- extend coverage beyond adjudication into import, assessment, scheduling, and incubatee progression.

## Phase 4: Assets

Goal: harden transactional operational controls.

- define assignment policy rules,
- clarify department versus project assignment authority,
- add tests for reassignment and return invariants,
- decide whether retired, lost, and damaged states need explicit policies and audit events.

## Phase 5: Staff + Leave + Human Resources

Goal: remove controller leakage and formalize approval policy.

- extract leave orchestration out of controller-heavy flow,
- formalize manager and HR approval policies,
- add transaction rules for approval and rejection states,
- define staff lifecycle rules tied to roles and departments.

## Phase 6: Supporting Domains

Goal: make supporting CRUD domains consistent with the hardened architecture.

- stakeholders,
- facilitators,
- programs,
- organization,
- events.

These should be normalized after the operational domains establish the final pattern.

Status:

- Started. `Organization` is now a centralized institutional-profile domain with shared organization data available to Inertia views, and `Events` is now a standalone annual-event workflow with speakers, attendees, attendance-state management, annual-series rollups, and printable event reporting separate from `Projects`.

## Working Method For Each Domain

For every domain we touch next, use this sequence:

1. inventory routes, models, requests, services, repositories, pages, and tests
2. define the domain lifecycle states and transaction boundaries
3. define policy matrix for view, create, update, transition, and destructive actions
4. move leaked business rules into services and policies
5. add regression tests
6. update docs before leaving the domain

## Best First Active Work Item

Start with:

`Projects + Beneficiaries baseline hardening`

Reason:

- highest business centrality,
- deepest workflow complexity,
- likely largest long-term payoff,
- best place to establish transaction-policy standards for the rest of the platform.

Current next step:

- Continue with `Projects + Beneficiaries` by pulling facilitator-scoped progress, attendance, and milestone-assessment authorization plus enrollment state transitions into dedicated service or policy seams.

Progress update:

- `ProjectAccessService` now centralizes facilitator resolution, assigned-location access, admin/full-project access, and project-manager summary checks for the project workflow controllers.
- Enrollment consistency is now enforced by shared services rather than repeated controller assumptions.
- Attendance capture, holiday marking, and milestone assessment state rules now live in dedicated workflow services instead of remaining embedded in controller request flow logic.
- Project progression is now guarded at create-time as well as update-time, and completion rules explicitly ignore dropped beneficiaries while still requiring every active beneficiary to complete every milestone.
- Project list and detail surfaces now expose domain-backed status readiness, allowed transitions, and blocker messages so workflow constraints are visible before a mutation is attempted.
- Attendance registers and milestone assessments now have explicit project-activity policies, with controller authorization tied to role plus project-state rules instead of relying only on route middleware and ad hoc access checks.
- Attendance capture is now limited to active projects and a one-day correction window, future attendance dates are blocked, and milestone scores can only be created or corrected while the project remains active.
- Projects now support one sponsor plus multiple implementation partners as a first-class commercial structure.
- `ProjectProgressService` now computes project and per-location delivery rollups for milestones, beneficiary completion, attendance health, blocked sites, and project-manager portfolio tracking.
- The project detail page now acts as a real project-manager control surface, and the projects dashboard now exposes a portfolio table plus intervention-focused rollup metrics.
- Project conclusion is now an explicit governance workflow with a dedicated closure record, sign-off notes, automatic final-report generation, and PDF report output.
- Projects can now generate first-class progress and final reports with delivery snapshots instead of relying only on live dashboard state.
- Projects now capture commercial/reporting metadata such as contract reference, funding amount, reporting cadence, and reporting obligations.
- Project closure now supports evidence uploads and a project-level audit timeline, so governance activity is traceable beyond the live UI state.
- `Organization` now provides a single managed source of truth for institutional profile content such as name, mission, vision, values, service offering, and contact details.
- `Events` now exists as a separate annual institutional-events domain with event ownership, speakers, attendees, attendee status progression, annual-series history, and downloadable event reports independent of beneficiary/project delivery workflows.
