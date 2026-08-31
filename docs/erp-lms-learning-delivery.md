# ERP-LMS Learning Delivery Integration

## Ownership

- ERP owns beneficiaries, facilitators, project membership, and project audit history.
- LMS owns learning access, cohorts, course execution, attendance, assessments, certificates, and learning roles.
- LMS learner records link back to ERP beneficiaries through `lms_learners.erp_beneficiary_id`.
- LMS facilitator records link back to ERP facilitators through `lms_facilitators.erp_facilitator_id`.
- LMS admin users remain LMS-native and are not projected from ERP beneficiaries or facilitators.

## ERP Workflow

1. Open a project in ERP.
2. Use the Learning Delivery panel to fetch LMS cohorts as available learning offerings.
3. Map the ERP project to one LMS cohort.
4. Provision eligible project beneficiaries. ERP checks local eligibility first, then asks LMS to link or invite learners.
5. Provision eligible project facilitators. ERP checks project facilitator assignment and email availability first, then asks LMS to link or invite facilitators.
6. Assign an eligible facilitator to teach the mapped LMS cohort. LMS validates eligibility back against ERP before attaching the teaching role.
7. Use the project, beneficiary, and facilitator pages in ERP to view LMS summaries without owning LMS execution data.
8. Use beneficiary/facilitator LMS panels to resend pending or expired invitations.
9. Beneficiary suspend/reinstate actions ask LMS to suspend/reactivate access while preserving LMS history.

## Access Lifecycle

```text
NOT PROVISIONED
       |
       v
INVITATION PENDING
       |
       +----> INVITATION EXPIRED
       |              |
       |           RESEND
       |              |
       +--------------+
       |
       v
     ACTIVE
       |
       v
   SUSPENDED
       |
       v
  REACTIVATED
```

## Identity Link

```text
ERP BENEFICIARY
       |
       | provision
       v
LMS USER ------ LMS LEARNER
       |             |
   Invitation    ERP Beneficiary ID
       |
       v
   Activation
       |
       v
     Login


ERP FACILITATOR
       |
       | provision
       v
LMS USER ------ LMS FACILITATOR
       |             |
   Invitation    ERP Facilitator ID
       |
       v
   Activation
       |
       v
     Login
```

## Duplication Rules

- ERP does not create LMS-only copies of beneficiaries or facilitators.
- LMS records store the ERP identity key and learning-specific state.
- Pending LMS invitations hold onboarding snapshots only and are not canonical person records.
- ERP `project_learning_mappings` stores the project-to-offering mapping and offering snapshot for auditability, while LMS `lms_project_cohort_mappings` stores the execution-side mapping.
- ERP never generates, stores, displays, or resets LMS passwords.

## Invitation And Activation

- Provisioning creates or reuses an LMS invitation when a learner/facilitator profile does not exist.
- The recipient activates LMS access from the LMS invitation link and chooses their own password.
- LMS stores newly issued invitation tokens as hashes.
- Resend invalidates the old token, extends expiry, increments resend count, and sends a new activation link.
- After activation, ERP summaries report activation and last-login timestamps from LMS.
- Forgot password remains the LMS-owned Laravel password reset flow.

## Access States

- `not_provisioned`: no LMS profile or active invitation.
- `invitation_pending`: unaccepted invitation, still within expiry.
- `invitation_expired`: unaccepted invitation beyond expiry.
- `active`: LMS user and learning profile are active.
- `suspended`: LMS user/profile is blocked.
- `reactivated`: represented as `active` after LMS lifecycle reactivation.

## ERP Panels

- Project Learning Delivery shows mapped cohorts plus access counts for active, pending, expired, suspended, and not provisioned identities.
- Beneficiary LMS Access shows status, invitation state, activation date, last login, progress, and a resend action when applicable.
- Facilitator LMS Teaching Access shows status, invitation state, activation date, last login, cohort count, learner count, and resend action when applicable.

## Current Contract

ERP calls LMS using `LmsLearningDeliveryClient` and `X-LMS-BRIDGE-TOKEN`:

- `GET /integrations/erp/learning-offerings`
- `GET /integrations/erp/learning-offerings/{cohort}`
- `POST /integrations/erp/project-mappings`
- `POST /integrations/erp/provisioning/learners`
- `POST /integrations/erp/provisioning/facilitators`
- `POST /integrations/erp/teaching-assignments`
- `POST /integrations/erp/invitations/resend`
- `POST /integrations/erp/access-lifecycle`
- `GET /integrations/erp/beneficiaries/{erpBeneficiaryId}/learning-summary`
- `GET /integrations/erp/facilitators/{erpFacilitatorId}/learning-summary`
- `GET /integrations/erp/projects/{erpProjectId}/learning-summary`

LMS calls ERP for canonical lookup and facilitator teaching eligibility:

- `GET /integrations/lms/beneficiaries/lookup`
- `GET /integrations/lms/facilitators/lookup`
- `GET /integrations/lms/projects/{project}/facilitators/{facilitator}/teaching-eligibility`

## Deferred Work

- Queue-based lifecycle synchronization for beneficiary exits, project closure, and cohort completion.
- SSO/session handoff between ERP and LMS.
- Event-driven notifications after provisioning and teaching assignment changes.
- More granular LMS analytics once assessment domain rules are finalized.
- SMS/OTP authentication if future ERP data analysis shows email coverage is insufficient.
- Existing sessions are not force-revoked at suspension time; suspended users are blocked on subsequent login and protected LMS requests.
