# PTPI Organization Blueprint

This blueprint defines how PTPI should be organized inside AB4IRERP so it remains usable, scalable, and aligned with the existing domain-driven Laravel plus Inertia architecture.

It is intentionally structured around information architecture, user intent, and domain ownership first. UI styling and motion come after that.

## 1. Organizing Principle

PTPI should not be organized as a loose collection of pages.

PTPI should be organized across five layers:

1. user journeys
2. information architecture
3. domain ownership
4. reusable UI sections
5. optional motion and presentation polish

This keeps PTPI maintainable even as new workflows, reports, and pages are added.

## 2. PTPI Core Questions

Every PTPI screen or section should answer all of these:

- who is the user
- what is the user trying to do
- what information is required to do it
- what decision or action should happen next
- which domain owns that data and workflow

If a PTPI section does not answer those clearly, it is likely display noise and should be reduced, merged, or removed.

## 3. Recommended User Journey Model

PTPI should be organized around user journeys instead of menu-first navigation.

Recommended top-level journey buckets:

1. Discover
   - what PTPI is
   - what programs, services, or opportunities exist
   - who the target audience is

2. Understand
   - eligibility, requirements, deadlines, process, supporting material
   - FAQs, documentation, and evidence

3. Apply or Submit
   - forms, document upload, declarations, progress, and confirmation

4. Track
   - application status
   - review progress
   - assigned actions
   - notifications

5. Engage
   - sessions, events, onboarding, communications, support

6. Administer
   - internal operations
   - assessment
   - approval
   - reporting
   - audit trail

This gives PTPI a clean mental model for both public-facing and internal-facing workflows.

## 4. Information Architecture Model

Each PTPI feature should be classified into one of these content types:

- orientation content
  - introductory overview, purpose, summary, key dates
- decision content
  - eligibility, criteria, requirements, next steps
- workflow content
  - forms, tasks, approvals, submissions, review stages
- evidence content
  - documents, reports, attachments, history, audit events
- support content
  - FAQs, contact, troubleshooting, guidance

Each page should have one primary content type and at most one secondary type.

If a page tries to be all five at once, it should be split.

## 5. PTPI Page Composition Standard

Every major PTPI page should follow a predictable section hierarchy:

1. Context header
   - page title
   - one-sentence purpose
   - status or phase badge where relevant

2. Key summary block
   - the 3 to 6 most important facts or actions

3. Main workflow block
   - primary form, table, review panel, or tracking state

4. Supporting evidence block
   - attached files, reports, notes, activity, references

5. Next actions block
   - what the user can do now
   - what happens after this

6. Secondary help block
   - FAQ, support contact, or policy references

This reduces cognitive overload and makes complex workflow screens easier to scan.

## 6. Navigation Model

PTPI navigation should be shallow and intent-based.

Recommended navigation shape:

- Overview
- Opportunities or Programs
- Applications
- Tracking
- Events or Engagement
- Resources
- Administration

Avoid overly deep nested navigation unless it maps directly to a real operational workflow.

Navigation labels should be user-intent labels, not internal data labels.

Good:

- My Applications
- Track Progress
- Submit Documents
- Upcoming Sessions

Weaker:

- Records
- Entries
- Registry
- Data

## 7. Component Strategy

PTPI should use reusable section components instead of page-specific layout code.

Recommended reusable components:

- `PageContextHeader`
- `SummaryStatStrip`
- `WorkflowStepList`
- `StatusTimeline`
- `DocumentPanel`
- `EvidenceFeed`
- `ActionPanel`
- `EligibilityChecklist`
- `RequirementsList`
- `SupportPanel`
- `EmptyStateCard`
- `PhaseBadge`

Recommended page-level composition rule:

- pages should compose sections
- sections should compose cards, lists, forms, and tables
- business logic should stay outside section components

This matches the existing Inertia + React structure better than embedding workflow logic directly in large page files.

## 8. Domain Ownership Inside This Repo

PTPI should not become a separate unmanaged island. It should map cleanly onto the current domain-driven backend structure.

Recommended ownership split:

- discovery and public program information
  - `Programs`
  - `Projects`
  - `Stakeholders`

- application and intake workflows
  - `BusinessDevelopment`
  - possibly a dedicated `Ptpi` domain if PTPI has unique lifecycle rules

- participant or beneficiary progression
  - `Beneficiaries`
  - `Projects`

- events, orientation, and engagement
  - `Events`

- internal review and approvals
  - `BusinessDevelopment`
  - `AccessControl`
  - `Notifications`

- staff operations and follow-up
  - `Staff`
  - `TaskManagement`

If PTPI introduces workflow rules that do not fit existing domains cleanly, create a dedicated `Ptpi` domain slice instead of scattering logic across controllers.

Recommended future backend shape if PTPI becomes first-class:

- `app/Domains/Ptpi/Models`
- `app/Domains/Ptpi/Repositories`
- `app/Domains/Ptpi/Services`
- `app/Domains/Ptpi/Requests`
- `app/Domains/Ptpi/Resources`
- `app/Domains/Ptpi/Policies`
- `app/Domains/Ptpi/Controllers`

## 9. Frontend Placement

Recommended frontend structure if PTPI becomes a first-class domain:

- `resources/js/pages/PTPI/...`
- `resources/js/components/ptpi/...`
- `resources/js/config/domain-nav/ptpi.tsx`
- `resources/js/types/ptpi.ts`

Recommended page grouping:

- `resources/js/pages/PTPI/Overview.tsx`
- `resources/js/pages/PTPI/Programs/Index.tsx`
- `resources/js/pages/PTPI/Applications/Index.tsx`
- `resources/js/pages/PTPI/Applications/Show.tsx`
- `resources/js/pages/PTPI/Applications/Create.tsx`
- `resources/js/pages/PTPI/Tracking/Index.tsx`
- `resources/js/pages/PTPI/Events/Index.tsx`
- `resources/js/pages/PTPI/Admin/Dashboard.tsx`

Recommended component grouping:

- `resources/js/components/ptpi/page-context-header.tsx`
- `resources/js/components/ptpi/status-timeline.tsx`
- `resources/js/components/ptpi/evidence-feed.tsx`
- `resources/js/components/ptpi/action-panel.tsx`
- `resources/js/components/ptpi/eligibility-checklist.tsx`

## 10. Data and Knowledge Organization

PTPI information should be separated into source material, structured records, and presentation-ready output.

Recommended distinction:

- source
  - uploaded files, external references, imported datasets
- workflow records
  - applications, reviews, tasks, statuses, events
- derived presentation data
  - dashboards, progress summaries, public overviews
- knowledge artifacts
  - internal notes, policy guides, implementation references

For PTPI-related research and extracted references, use a pattern similar to the subtitle extraction repository:

- raw source preserved
- processed interpretation stored separately
- summary notes derived from source
- stable schema where repeatable structure matters

## 11. Motion and UI Rules

Motion should clarify state and hierarchy, not decorate screens.

Allowed motion uses:

- section entrance on first reveal
- step or status transitions
- tab or panel switching
- progress state changes
- expansion and collapse for evidence or details

Avoid:

- continuous decorative animation on operational screens
- large movement that delays task completion
- animation on dense tables unless it improves orientation

Recommended PTPI motion style:

- short duration
- subtle easing
- predictable direction
- no motion that hides critical actions or state

For PTPI, motion is most justified on:

- overview or landing surfaces
- multi-step application flows
- status tracking timelines
- onboarding or engagement pages

## 12. Content Design Rules

PTPI content should be written with operational clarity.

Each section should answer:

- what this is
- why it matters
- what the user must do
- what happens next

Avoid large undifferentiated text blocks.

Prefer:

- short summaries
- checklists
- labeled facts
- phase indicators
- clear next actions

## 13. Access and Workflow Rules

PTPI should follow the repo's existing direction:

- route-level access control
- service-layer workflow orchestration
- policy checks for stateful decisions
- transaction boundaries for multi-record changes

Do not make PTPI controller-heavy.

If PTPI supports application submission, review, approval, reassignment, or status changes, those workflows should live in services and policies, not page controllers.

The best internal template for PTPI workflow quality is the stronger `BusinessDevelopment` and adjudication style already present in the repo.

## 14. Reporting and Auditability

PTPI should be designed for audit from the start.

Recommended built-in audit elements:

- who performed an action
- when it happened
- previous and new status
- supporting note or reason
- linked file or evidence where relevant

Recommended reusable UI blocks:

- status history
- activity feed
- submission receipt
- review decision summary

## 15. Suggested Implementation Phases

### Phase 1: Information architecture

- define PTPI users
- define journeys
- define top-level navigation
- define primary page inventory
- define content types per page

### Phase 2: Frontend structure

- add PTPI page folder
- add PTPI domain nav config
- add shared PTPI section components
- create 2 to 3 representative pages using the shared composition pattern

### Phase 3: Backend workflow ownership

- decide whether PTPI fits existing domains or needs `Ptpi`
- move submission and review logic into service workflows
- add policies for any stateful actions
- add resources and request classes

### Phase 4: Operational hardening

- add tests for access
- add tests for workflow transitions
- add activity/audit visibility
- add notifications where needed

### Phase 5: Motion and polish

- add subtle transitions only after workflow clarity is correct
- apply polished motion to overview, tracking, and onboarding surfaces

## 16. Immediate Recommendation For This Repo

The fastest safe path is:

1. treat PTPI as a first-class frontend area under `resources/js/pages/PTPI`
2. reuse existing backend domains where the ownership is already clear
3. introduce a dedicated backend `Ptpi` domain only if PTPI has distinct lifecycle rules that cut across `Programs`, `BusinessDevelopment`, `Projects`, and `Events`
4. standardize PTPI pages around context, summary, workflow, evidence, and next actions
5. keep motion minimal until the information architecture is stable

## 17. Definition of Done For PTPI Organization

PTPI is well organized when:

- a user can tell where to start immediately
- each page has one clear primary job
- navigation reflects user intent
- backend ownership is unambiguous
- workflow logic is service-owned
- state changes are policy-aware and auditable
- the frontend is built from reusable sections instead of page-by-page improvisation
