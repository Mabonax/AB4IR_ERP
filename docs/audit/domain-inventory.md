# Domain Inventory

This inventory focuses on delivery maturity, enforcement maturity, and the likely optimization value of each domain.

## Domain Summary

| Domain | Current Shape | Maturity | Main Strength | Main Gap |
| --- | --- | --- | --- | --- |
| Assets | services, repositories, models, requests, resources, controllers, multiple pages | High | transactional assignment and return flow | frontend typing drift, limited dedicated tests |
| Beneficiaries | service, repository, model, requests, resource, controller, pages | Medium | beneficiary plus next-of-kin plus enrollment transaction | weak policy coverage and no dedicated tests |
| BusinessDevelopment | services, repositories, adjudication subdomain, policies, requests, resources, controllers, pages | High | strongest workflow and policy-aware domain | active WIP and limited coverage outside adjudication |
| Facilitators | service, repository, model, requests, resource, controller, pages | Medium | clean domain slice and role linkage | mostly CRUD-level maturity |
| HumanResources | dashboard controller and UI surface | Low-Medium | visible domain entry point exists | thin domain layer and little encapsulated logic |
| Leave | controller, model, service, pages | Medium | real approval workflow exists | too much workflow logic still lives in controller |
| Programs | service, repository, model, requests, resource, controller, pages | Medium | stable supporting domain | mostly CRUD-level maturity |
| Projects | services, repositories, many models, requests, resources, 6 controllers, multiple pages | High | deepest operational workflow surface | needs stronger tests and policy formalization |
| Staff | services, repositories, models, requests, resources, controllers, pages | Medium-High | staff profile and department scaffolding is well formed | needs stronger workflow and policy hardening |
| Stakeholders | service, repository, models, requests, resource, controller, pages | Medium | stakeholder plus contact transactions exist | mostly CRUD-level maturity |

## Domain Notes

## Projects

- Strongest operational core.
- Includes projects, locations, enrollments, attendance, milestones, milestone templates, dashboards, and PDF exports.
- `ProjectService` already syncs milestones transactionally after project creation.
- `ProjectAttendanceController` contains substantial access and workflow logic that should eventually be partially moved into dedicated policies/services.

References:
- [ProjectService.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/Projects/Services/ProjectService.php:35)
- [ProjectAttendanceController.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/Projects/Controllers/ProjectAttendanceController.php:126)

## Business Development

- Most mature workflow domain from a business-rules perspective.
- Supports application import, assessment, pitch scheduling, adjudication, incubatee conversion, and unlock flow.
- This is currently the best example of transactional workflow plus explicit policy enforcement.

References:
- [BdsApplicationService.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/BusinessDevelopment/Services/BdsApplicationService.php:40)
- [AdjudicationAssessmentService.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/BusinessDevelopment/Adjudication/Services/AdjudicationAssessmentService.php:47)
- [AdjudicationAssessmentPolicy.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/BusinessDevelopment/Adjudication/Policies/AdjudicationAssessmentPolicy.php:8)

## Assets

- Operationally meaningful.
- Handles asset registration, batches, assignment history, reassignments, returns, and manager dashboards.
- Good candidate for transaction-policy hardening because the workflow is already explicit.

Reference:
- [AssetService.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/Assets/Services/AssetService.php:38)

## Beneficiaries

- More than CRUD already.
- Creation and update include next-of-kin handling and project enrollment linkage in one transaction.
- Important because it likely sits near the center of incubator beneficiary operations.

Reference:
- [BeneficiaryService.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/Beneficiaries/Services/BeneficiaryService.php:42)

## Staff and Leave

- Staff structure is present and reasonably mature.
- Leave has real approval behavior, but the orchestration is still controller-heavy.
- This area should be treated as a workflow-hardening pass, not just a refactor pass.

Reference:
- [LeaveRequestController.php](/C:/xampp/htdocs/AB4IRERP/app/Domains/Leave/Controllers/LeaveRequestController.php:20)

## Supporting Domains

- `Programs`, `Stakeholders`, and `Facilitators` have solid domain slices.
- They appear stable enough to optimize after the operational core domains are hardened.

## First Domain Candidates

Best candidates to start active improvement work:

1. `Projects`
2. `Beneficiaries`
3. `BusinessDevelopment`

These domains have the highest combination of:

- central business value,
- existing complexity,
- transaction sensitivity,
- optimization payoff.
