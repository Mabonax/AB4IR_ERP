# Beneficiary Lifecycle Phase 1

This phase implements governed lifecycle transactions for the beneficiary domain inside the existing Laravel service and Inertia workflow structure.

## Delivered transactions

- Enroll beneficiary through the existing create flow with default lifecycle status `enrolled`
- Suspend beneficiary
- Reinstate beneficiary
- Transfer beneficiary
- Graduate beneficiary
- Exit beneficiary
- Archive beneficiary

## Persistence

- Added lifecycle fields directly on `beneficiaries`
- Added `beneficiary_outcomes` for recorded outcomes linked to beneficiary, program, and project
- Added `beneficiary_history` for timeline and audit entries

## Transaction rules

- All lifecycle transitions run through `BeneficiaryLifecycleService`
- Every transition records actor, timestamps, reason, and status movement
- Graduate and exit transitions record an outcome, defaulting to `unknown_outcome` when none is supplied
- Transfer updates the current project placement and preserves prior enrollment history by dropping the old active enrollment
- Archive uses the existing soft-delete path after recording lifecycle state

## UI exposure

- Beneficiary show page exposes lifecycle status, outcome summary, transaction actions, and a timeline
- Beneficiary directory exposes lifecycle metrics for the selected project iteration

## Reporting metrics

- Graduated beneficiaries
- Exited beneficiaries
- Employment outcomes
- Further education outcomes
- Unknown outcomes
