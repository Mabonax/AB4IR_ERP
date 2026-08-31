# ERP-LMS Access Lifecycle Audit

Audit date: 2026-08-24

## Working

- ERP owns beneficiary and facilitator identity records, lifecycle, project enrolments, project assignments, and project audit history.
- LMS owns user authentication, learner/facilitator learning profiles, cohorts, enrolments, teaching assignments, progress, attendance, assessments, and certificates.
- LMS learner profiles link to ERP beneficiaries through `lms_learners.erp_beneficiary_id`.
- LMS facilitator profiles link to ERP facilitators through `lms_facilitators.erp_facilitator_id`.
- LMS self-registration is blocked; user creation happens through invitation acceptance.
- Invitation acceptance lets the recipient choose their own password.
- ERP can map projects to LMS cohorts and request learner/facilitator provisioning.
- ERP can read LMS summaries on project, beneficiary, and facilitator pages.
- ERP project Learning Delivery can provision eligible learners/facilitators and request teaching assignments.

## Partial Before This Phase

- Invitation expiry existed in LMS, but ERP did not show expired versus pending clearly.
- LMS user status could be suspended, but login did not reject suspended accounts.
- ERP lifecycle actions did not notify LMS to suspend/reactivate access.
- Project Learning Delivery did not distinguish active, pending, expired, suspended, and not provisioned per person.

## Missing Before This Phase

- Hashed invitation token storage for newly issued invitations.
- Governed resend that invalidates the old invitation token.
- Last-login tracking.
- Learner activation timestamp.
- LMS bridge endpoints for resend and access lifecycle.
- ERP profile actions for LMS invitation resend.
- Explicit access-state normalization.

## Broken Or Unsafe Before This Phase

- Raw invitation tokens were exposed in the LMS admin invitation list.
- Login allowed suspended user credentials to authenticate before any route-level block.
- ERP could not prove activation versus invitation.

## Implemented Recommendation

- Newly issued invitation tokens are hashed.
- Resend replaces the token, extends expiry, increments resend count, and sends a fresh invitation.
- Login tracks `last_login_at` and rejects inactive/suspended accounts.
- LMS summaries normalize access state for ERP.
- ERP profile pages show LMS invitation/activation/login state and resend actions.
- ERP beneficiary suspend/reinstate actions call LMS lifecycle endpoints while preserving learning history.
- ERP still never generates, stores, displays, or resets LMS passwords.
