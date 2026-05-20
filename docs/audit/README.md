# Phase 0 Audit Pack

This pack establishes the current project baseline before domain-by-domain optimization, policy hardening, and business-logic refinement.

## Snapshot

- Stack: Laravel 12, PHP 8.2+, Inertia.js, React 19, TypeScript, Tailwind 4, Spatie permissions, DomPDF.
- Application shape: 10 backend domains in `app/Domains`, 160 web routes, 50 migrations, 59 Inertia page files, 26 controller files.
- Architecture direction: domain-oriented Laravel structure with service and repository layers already in place across most operational modules.
- Current branch state: repository is not clean; active uncommitted work exists in the Business Development adjudication area.

## Health Check

- `php artisan test`: passing. `134` tests passed.
- `npm run types`: passing.
- `npm run build`: passing, but the built `custom-table` chunk exceeds the Vite warning threshold.

## What This Means

The project has progressed beyond an experimental starter. It already contains meaningful enterprise workflow logic, permission modeling, and transactional services. The main risk is not missing architecture. The main risk is uneven enforcement maturity:

- route-level permission checks are broad,
- model policies are narrow,
- frontend typing is now stable enough to act as a regression gate, but some shared frontend abstractions remain heavy,
- tests no longer represent the current permission model in several areas,
- documentation and deployment artifacts are thin.

## Files In This Pack

- [Domain Inventory](./domain-inventory.md)
- [Architecture Findings](./architecture-findings.md)
- [Execution Backlog](./execution-backlog.md)

## Recommended Starting Order

1. Cross-cutting foundation
2. Projects + Beneficiaries
3. Business Development
4. Assets
5. Staff + Leave + Human Resources
6. Stakeholders + Facilitators + Programs

## Immediate Goal For Phase 1

Stabilize the platform baseline first:

1. Restore a trustworthy red/green test baseline. Completed.
2. Define a repeatable policy pattern for each domain. Started with `Projects` and `Beneficiaries`.
3. Document transaction boundaries and workflow states before changing behavior.
4. Move controller-owned business rules into services and policies where they are still leaking through.
