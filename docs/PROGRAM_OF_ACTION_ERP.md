# Programme of Action ERP

## Overview

Programme of Action ERP (POA ERP) is the current organisation ERP platform. The Laravel 12, Inertia React, TypeScript, Tailwind, and Spatie permission stack remains intact while the platform expands into a system for governance, compliance, programme delivery, funding, volunteering, assets, and reporting.

## Migration Principles

- Preserve existing domains, tables, and business workflows.
- Extend the current domain-driven structure instead of rebuilding the platform.
- Keep service and repository boundaries intact.
- Keep mobile-safe and API-safe patterns intact for future Flutter alignment.
- Add governance and compliance concerns through new domain slices and permissions.

## Preserved Domains

- Users
- Roles and permissions
- Beneficiaries
- Stakeholders
- Projects
- Tasks and support tickets
- Business development
- HR and leave management
- Documents and generated reports
- Notifications
- Reporting surfaces

## New Domain Roadmap

The POA migration adds the following domain slices under `app/Domains`:

- `Organisation`
- `Governance`
- `Committees`
- `Meetings`
- `Resolutions`
- `Policies`
- `Compliance`
- `Funding`
- `Donors`
- `Grants`
- `Volunteers`
- `Assets`
- `Procurement`
- `MonitoringEvaluation`
- `RiskManagement`
- `PublicBenefitOrganisation`
- `Reporting`

Each domain follows the existing convention:

- `Models`
- `Services`
- `Repositories`
- `Interfaces`
- `Requests`
- `Resources`
- `Policies`
- `Actions`
- `Events`
- `Listeners`

## Implemented Foundation In This Reinitialisation

- Branch baseline created from `main` as `program-of-action-erp`.
- Central branding service added for the platform name, logo, support email, and PDF footer text.
- Inertia shared brand payload added so auth screens, navigation branding, and browser titles can resolve from one source.
- `Organisation` domain backbone added with model, migration, repository, service, request, resource, policy, action, event, and listener.
- Access control expanded with POA governance, compliance, funding, volunteering, M&E, and reporting domains.
- Seed roles added for executive, board, compliance, funding, volunteer, and monitoring functions.

## Core Relationships

- `Organisation` owns governance structures, compliance records, projects, donors, and assets.
- `Programmes` and `Projects` remain the delivery backbone and connect to beneficiaries, volunteers, and impact tracking.
- `Funding`, `Donors`, and `Grants` connect to programme allocations and reporting obligations.
- `MonitoringEvaluation` connects programme outputs, outcomes, survey evidence, and donor reporting.
- `Reporting` remains cross-cutting and should aggregate from governance, delivery, funding, and compliance domains.

## Dependency Notes Before Deep Feature Work

- Existing `Organization` domain already manages organisation profile, branding assets, and the official vault. POA work should extend that domain rather than replace it.
- Existing `Assets`, `Projects`, `Beneficiaries`, `Stakeholders`, `Marketing`, `Staff`, `Leave`, and `TaskManagement` domains already contain live workflows and must be treated as stable dependencies.
- Dashboard surfaces currently emphasise work management and support operations. Executive POA widgets should be layered on top of those existing controllers and services.

## Future Roadmap

1. Add CRUD controllers, policies, routes, and Inertia pages for `Organisation`, `Governance`, `Compliance`, `Funding`, `Volunteers`, and `MonitoringEvaluation`.
2. Add meeting notices, attendance registers, minutes, and resolution document generation.
3. Add compliance calendar jobs, reminder notifications, and submission evidence tracking.
4. Add donor agreements, grant utilisation reporting, and allocation reporting to programmes and projects.
5. Add volunteer placement, hours, and certificate generation workflows.
6. Add executive dashboard cards backed by real governance, compliance, funding, and impact queries.
