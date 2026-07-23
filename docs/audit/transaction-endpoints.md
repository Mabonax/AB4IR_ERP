# Transaction Endpoints Audit

This document maps the implemented domain transactions to their logical completion points and highlights where the platform still stops at CRUD without a formal operational endpoint.

## Endpoint Types

- `completed`: delivery or workflow finished and closed.
- `approved`: work accepted by the governing role, often before downstream publishing or rollout.
- `closed`: requester or governing actor confirms the transaction is finished.
- `rejected` or `cancelled`: negative terminal outcome.
- `archived` or `retired`: record remains in history but is no longer active.
- `published`: output is promoted for organizational or external use.

## Domain Findings

## Access Control

- `Create/update/delete role` completes when the role record and its permissions are persisted.
- `Create/update/delete permission` completes when the permission record is persisted.
- `Assign user roles/permissions` completes when the user assignment snapshot is synchronized.
- Gap: access control has no approval, review, or audit-signoff workflow; completion is purely administrative persistence.

## Assets

- `Register asset` completes when an asset or batch is recorded and appears in the register.
- `Assign asset` completes when an active assignment exists and the asset status becomes `assigned`.
- `Return asset` completes when the active assignment receives `returned_at` and the asset returns to `unassigned`.
- `Start maintenance` completes when the active assignment is closed and the asset status becomes `maintenance`.
- `Complete maintenance` completes when the maintenance record status becomes `completed` and the asset returns to the available pool.
- `Decommission asset` completes when a decommission record exists and the asset status becomes `retired`.
- `Report asset fault` currently completes by opening a technical support ticket in `TaskManagement`.
- Gap: the fault-report transaction does not return to the asset domain with a first-class `resolved/back_in_service` endpoint.

## Beneficiaries

- `Register beneficiary` completes when the beneficiary, next-of-kin, and current project enrollment are saved in one transaction.
- `Import beneficiaries` completes when every row is classified as `created`, `matched existing`, or `rejected duplicate`, with import errors surfaced.
- `Transfer beneficiary` completes when the previous active enrollment is dropped and the new project enrollment becomes `enrolled`.
- `Update beneficiary profile` completes when profile and enrollment alignment remain valid for the selected project and location.
- `Archive beneficiary` completes when the record is soft deleted and removed from the active directory.
- Gap: there is no explicit restore or reactivation transaction, and no dedicated beneficiary-owned `graduate/exit` endpoint beyond enrollment state changes and project closure.

## Business Development

- `Import application` completes when the application row is created and available for screening.
- `Assess application` completes when the application receives a screening outcome such as `accepted` or `rejected`.
- `Schedule pitch` completes when an accepted application gets a future pitch date or is attached to a pitch session.
- `Submit adjudication scorecard` completes when a panelist submits a scorecard; that individual scorecard becomes immutable unless unlocked.
- `Unlock submitted scorecard` completes when an admin returns the scorecard to `draft`.
- `Consolidate panel outcome` completes when submitted scorecards are aggregated for the prospect.
- `Approve prospect outcome` completes when the manager records the final decision; an `incubated` decision also creates an active incubatee.
- Gap: incubatee lifecycle closure is still thin. Intake-to-incubation is explicit, but graduation, inactivity, and exit endpoints still need formal transaction definitions.

## Documents

- `Provision library roots` completes when program or project creation automatically creates the owned folder tree.
- `Create folder` completes when the nested folder is stored inside the same ownership scope.
- `Upload file` completes when a versioned file is stored and downloadable.
- `Publish file to organization vault` completes when an organization document is created by reference to the library file.
- Gap: the library has no native review, approval, supersession, or retirement workflow before publication; it is a controlled storage surface, not yet a governed document lifecycle.

## Events

- `Create event` completes when the event is stored and its planning template/workstreams are provisioned.
- `Capture or import participants` completes when participants are stored with category and attendance metadata.
- `Update participant status` completes when the participant reaches a recorded attendance state such as `checked_in` or `attended`.
- `Manage workstream task` completes when the task status, evidence, and planning details are updated.
- `Submit outcome report` completes when post-event reporting is saved with `report_status`.
- `Generate event reports/registers` completes when the PDF or CSV output is available for the event.
- Gap: the domain has post-event reporting, but no explicit governed `conclude event` transaction that transitions the event itself into a terminal operational state with closure rules.

## Facilitators

- `Register facilitator` completes when the facilitator profile is saved and linked to a user account and facilitator role.
- `Update facilitator` completes when the profile and user linkage are synchronized.
- `Delete facilitator` completes when the record is removed.
- Gap: the domain has no operational lifecycle after onboarding. There is no explicit activation, suspension, deactivation, assignment acceptance, or offboarding endpoint.

## Finance

- `Submit travel claim` completes when the claim is stored with calculated totals and enters `submitted` plus `approval_status = pending`.
- `Executive approval` completes when the approval status becomes `approved`.
- `Approval rejection` completes when the approval path is rejected before finance processing.
- `Finance receive` completes when the approved claim moves into `received`.
- `Finance pay` completes when the claim status becomes `paid` and payment timestamps are stored.
- `Finance reject` completes when finance rejects the claim as a terminal negative outcome.
- This is a complete multi-step transaction with clear positive and negative endpoints.

## Human Resources

- `Department staffing review` is informational, not transactional.
- `Staff onboarding` completes when staff, linked user access, and next-of-kin are provisioned and the welcome notification is sent.
- Gap: HR does not yet expose explicit staff offboarding, suspension, termination, or rehire transactions with terminal states.

## Leave

- `Submit leave request` completes when the request is stored as `submitted`.
- `Manager approval` completes when the request reaches `manager_approved`.
- `Manager rejection` completes as a terminal negative outcome for that request.
- `HR approval` completes the positive workflow and applies leave balances.
- `HR rejection` completes the workflow as a terminal negative outcome.
- `Requester revoke` completes when the request becomes `cancelled`.
- `Upload supporting document` completes when evidence is stored and linked to the request.
- This is a complete approval workflow with explicit terminal states.

## Marketing

- `Create marketing job` completes when a routed work item exists in `open`.
- `Submit job for approval` completes when the job reaches `pending_approval` with proof attached.
- `Request amendments` completes by returning the job to `changes_requested`.
- `Approve marketing job` completes when the job reaches `approved` and is closed by the manager.
- `Create marketing request` completes when the request, work package, and deliverables are provisioned.
- `Upload deliverable version` completes when the deliverable enters internal review.
- `Approve deliverable` completes when the deliverable is approved and a reusable asset may be created.
- `Publish marketing asset` completes when the deliverable reaches `published` and publication plus metrics records exist.
- `Archive marketing asset` completes when `archived_at` is set.
- `Publish approved marketing output to organization vault` completes when the organization document is created or slot-replaced.
- This domain already has explicit work completion endpoints.

## Organization

- `Update organization profile` completes when the current institutional profile is persisted.
- `Update impact metrics` completes when a new organization metric snapshot is stored for history.
- `Upload logos` completes when the brand assets are stored.
- `Publish vault document` completes when the document is stored with audience and slot rules.
- `Deactivate/reactivate document` completes when `is_active` is toggled.
- `Retire document now` completes when the document is made inactive and receives an `effective_until` boundary.
- `Delete document` completes when the document record and stored file are removed.
- This domain has explicit lifecycle control for institutional documents.

## Programs

- `Create program` completes when the program record and default program-owned document folders are provisioned.
- `Update program` completes when the metadata is saved.
- `Delete program` completes when the record is removed.
- Gap: the program domain has overview reporting, but no explicit `close program`, `retire program`, or `roll over to next cohort` transaction. Program completion is currently inferred through projects, not owned by the domain itself.

## Projects

- `Create project` completes when the project record exists and its milestone template sync runs.
- `Activate project` completes only when readiness blockers are cleared: manager, location, and milestones must exist.
- `Add location` completes when the project receives a delivery site and facilitator linkage.
- `Enroll beneficiary` completes when the beneficiary is attached to a project and location through a valid enrollment.
- `Capture attendance` completes when a register is saved for the location and date.
- `Mark holiday` completes when the register records a holiday state for the date.
- `Store milestone assessment` completes when beneficiary delivery evidence is recorded for the project milestone.
- `Generate progress report` completes when a snapshot report is stored.
- `Upload closure evidence` completes when governance evidence is stored and written into project history.
- `Conclude project` completes when the project status becomes `completed`, `end_date` is set, a project closure exists, active enrollments are marked `completed`, and the automatic final report is generated.
- This is the clearest end-to-end operational lifecycle in the platform.

## Staff

- `Create staff member` completes when the staff profile, linked user, and next-of-kin are provisioned.
- `Promote manager` completes when manager status or role capability is updated.
- `Reset password` completes when a new credential is issued to the linked user.
- `Delete staff member` completes when the staff record is removed.
- Gap: there is no governed employment lifecycle for suspension, separation, exit clearance, or reactivation.

## Staff Attendance

- `Clock in` completes when a daily attendance record is created with the correct clock-in status.
- `Submit late request` completes when a pending override request is created for manager review.
- `Approve late request` completes when the override is granted and the eventual clock-in can proceed as `late_override`.
- `Clock out` completes when the attendance record has a closing time, whether self-recorded, scheduled prompt, or auto clock-out.
- `Export attendance report` completes when the report PDF is generated.
- This domain has a clear per-day completion state: the day is operationally complete once clock-out is recorded.

## Stakeholders

- `Register stakeholder` completes when the stakeholder record exists.
- `Add stakeholder contact` completes when the contact record is attached.
- `Update stakeholder` completes when the profile and contact data are synchronized.
- `Delete stakeholder/contact` completes when the selected record is removed.
- Gap: there is no stakeholder relationship lifecycle such as onboarding, active partnership, inactive partnership, renewal, or disengagement.

## Task Management

- `Create work task` completes when the task is opened and routed to a user or department queue.
- `Update task status` completes when the task reaches a new working state such as `in_progress` or `blocked`.
- `Submit task for review` completes when proof is attached and the task enters `pending_review`.
- `Approve task completion` completes when the task reaches `completed` and receives `closed_at` plus `closed_by_user_id`.
- `Return task for amendments` completes when the task reopens as `changes_requested`.
- `Create support ticket` completes when the ticket enters the technical queue.
- `Assign support ticket` completes when ownership moves to a responder and the ticket becomes `assigned`.
- `Resolve support ticket` completes the responder's work, but not the overall transaction.
- `Close support ticket` is the real terminal endpoint; it completes when the requester confirms the issue is solved and the ticket reaches `closed`.
- `Reopen support ticket` completes by returning the ticket to an active queue state.
- This domain already distinguishes work completion from transaction closure correctly.

## Highest-Priority Gaps

1. `Programs`, `Facilitators`, and `Stakeholders` still stop at CRUD and need owned lifecycle endpoints.
2. `Staff` and `HumanResources` need a governed employment end-state model for offboarding, suspension, and rehire.
3. `Beneficiaries` need explicit reactivation and graduation or exit transactions instead of relying only on enrollment state side effects.
4. `Events` need an event-owned closure transaction, not only manual status editing plus outcome reporting.
5. `Assets` need a loop-back endpoint from technical fault resolution to operational asset readiness.

## Recommended Next Implementation Order

1. Formalize endpoint enums and terminal-state language per domain.
2. Add dedicated transition actions and UI buttons for the gap domains instead of leaving completion implicit in edit forms.
3. Add one regression test per terminal transaction.
4. Expose completion criteria and blockers on the relevant detail pages, following the current `Projects` and `BusinessDevelopment` pattern.
