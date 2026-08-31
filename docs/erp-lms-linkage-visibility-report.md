# ERP-LMS Linkage Visibility Report

Date: 2026-08-24

## Scope

This investigation checked why ERP beneficiaries visible in the ERP project hierarchy are not visible as linked learners in the LMS, why LMS linkage buttons were not obvious on ERP profiles, and why some LMS users are not students.

## Current Runtime

- ERP is running at `http://127.0.0.1:8017`.
- LMS is running at `http://127.0.0.1:8016`.
- Bridge routes exist on both sides and reject unauthenticated bridge calls with `403`, which confirms the routes are registered and token protected.

## Live Data Findings

- ERP has 1 project: `Drone diva unisa`.
- ERP has 3 enrolled beneficiaries in that project.
- ERP has 0 project-to-LMS mappings in `project_learning_mappings`.
- LMS has 9 users.
- LMS has 2 learner identity links in `lms_learners`, but both are seeded/demo records:
  - `learner@ab4ir.org.za` linked to `ERP-BEN-SEED-6`
  - `entrepreneur@ab4ir.org.za` linked to `ERP-BEN-SEED-7`
- LMS has 1 facilitator identity link in `lms_facilitators`, also seeded/demo:
  - `facilitator@ab4ir.org.za` linked to `ERP-FAC-SEED-4`
- None of the LMS learner links match the real ERP beneficiary IDs `1`, `2`, or `3`.
- Two ERP beneficiaries currently share the same email address, `rose@example.co.za`. Because LMS user email is unique, these two ERP beneficiary records cannot both become separate LMS users with that same email.

## Root Cause

The bridge implementation is present, but the live ERP project has not been mapped to an LMS cohort. Because `Drone diva unisa` has no active LMS mapping, provisioning the project hierarchy into LMS has not been triggered for the real ERP beneficiaries.

The visible LMS learners are not the ERP hierarchy beneficiaries. They are seed data with fake ERP IDs, so the LMS cannot show the real ERP project beneficiaries until provisioning creates invitations or accounts using the real ERP beneficiary IDs.

The ERP profile pages had resend/open actions for existing LMS states, but no first-time profile-level `Provision LMS Access` button for a beneficiary or facilitator whose LMS state is `not_provisioned`.

The LMS user register showed users by role/status only. It did not clearly label whether a user is an ERP beneficiary learner, ERP facilitator, or native LMS staff/admin account.

## Implementation Applied

- Added ERP profile routes for first-time LMS access provisioning:
  - `POST /beneficiaries/{beneficiary}/lms-access/provision`
  - `POST /facilitators/{facilitator}/lms-access/provision`
- Added profile-level `Provision LMS Access` buttons for `not_provisioned` beneficiaries and facilitators.
- Updated LMS user management data so each user row includes:
  - account type
  - ERP link type
  - ERP link ID
  - LMS profile status
  - last login
- Updated the LMS users table to display non-student users as `LMS Native / Staff` instead of leaving the distinction ambiguous.

## Required Operational Next Step

Map ERP project `Drone diva unisa` to the LMS offering `Cohort 5` first. After that mapping exists, use either:

- the ERP project Learning Delivery panel to provision selected beneficiaries in bulk, or
- the beneficiary profile `Provision LMS Access` button to provision one beneficiary.

Before provisioning both Rose records, correct the duplicate email issue or decide that both ERP records intentionally represent one person. The LMS cannot create two separate user accounts with one email address.

## Expected Workflow

1. ERP owns beneficiary and facilitator master data.
2. ERP project is mapped to an LMS offering/cohort.
3. ERP provisions eligible beneficiaries or facilitators to LMS through the bridge.
4. LMS creates a pending invitation or updates an existing linked identity.
5. Beneficiary/facilitator accepts the LMS invitation and becomes an LMS user with an ERP identity link.
6. ERP profile pages read LMS summary data using the ERP identity ID and show access, invitation, login, progress, and deep-link state.

## Remaining Verification

The code path is now available, but live verification still depends on creating the project-to-cohort mapping in the ERP UI or database and provisioning a beneficiary with a unique email.
