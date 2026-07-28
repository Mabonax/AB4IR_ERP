# Architecture Findings

## 1. The DDD Direction Is Real

The project is clearly no longer a flat Laravel app. Repositories are bound centrally in [AppServiceProvider.php](/C:/xampp/program-of-action-erp/app/Providers/AppServiceProvider.php:49), most operational domains have service layers, and route ownership is organized around domain controllers in [routes/web.php](/C:/xampp/program-of-action-erp/routes/web.php:33).

Assessment:

- Good architectural direction.
- Strong enough foundation to keep building on.
- Worth consolidating rather than rewriting.

## 2. Authorization Is Broad But Not Yet Uniform

Route-level permission gates are consistently applied across the app in [routes/web.php](/C:/xampp/program-of-action-erp/routes/web.php:33). Access control seeding is also thoughtful and domain-based in [AccessControlSeeder.php](/C:/xampp/program-of-action-erp/database/seeders/AccessControlSeeder.php:19).

However, explicit model policy enforcement is narrow. The clearest policy implementation is adjudication, registered in [AppServiceProvider.php](/C:/xampp/program-of-action-erp/app/Providers/AppServiceProvider.php:127).

Implication:

- access is protected at route entry,
- but business-rule authorization is not yet consistently enforced inside all domain workflows,
- which becomes risky once workflows get more stateful or are reused from multiple entry points.

## 3. Transaction Boundaries Exist In The Right Places

Several domains already wrap state changes in database transactions:

- beneficiaries,
- stakeholders,
- staff,
- programs,
- projects,
- assets,
- adjudication.

This is a strong sign that the codebase is already thinking in workflow units, not only per-model CRUD.

Examples:

- [BeneficiaryService.php](/C:/xampp/program-of-action-erp/app/Domains/Beneficiaries/Services/BeneficiaryService.php:48)
- [ProjectService.php](/C:/xampp/program-of-action-erp/app/Domains/Projects/Services/ProjectService.php:37)
- [AssetService.php](/C:/xampp/program-of-action-erp/app/Domains/Assets/Services/AssetService.php:40)
- [AdjudicationAssessmentService.php](/C:/xampp/program-of-action-erp/app/Domains/BusinessDevelopment/Adjudication/Services/AdjudicationAssessmentService.php:55)

## 4. Controller Leakage Still Exists

Some domains still carry substantial workflow logic inside controllers. The biggest example is leave management in [LeaveRequestController.php](/C:/xampp/program-of-action-erp/app/Domains/Leave/Controllers/LeaveRequestController.php:20). Attendance control also mixes authorization, validation, and workflow orchestration inside [ProjectAttendanceController.php](/C:/xampp/program-of-action-erp/app/Domains/Projects/Controllers/ProjectAttendanceController.php:126).

Implication:

- controllers are still doing some service and policy work,
- which makes transaction policy hardening harder over time.

## 5. Frontend Abstractions Have Drifted

The shared React abstractions are useful, but typing has drifted from actual page usage.

High-signal hotspots:

- [custom-model-form.tsx](/C:/xampp/program-of-action-erp/resources/js/components/custom-model-form.tsx:22)
- [custom-table.tsx](/C:/xampp/program-of-action-erp/resources/js/components/custom-table.tsx:5)

Observed consequences from `npm run types`:

- shared field types are narrower than real usage,
- submit signatures no longer match current Inertia typing,
- icon typing is too loose,
- many page-level callbacks are still `any`.

This is now a platform issue, not a page issue.

## 6. Test Suite No Longer Matches The Real Access Model

The most visible failures come from settings/profile tests that assume unrestricted authenticated access. That assumption no longer holds because routes now require explicit permissions.

Reference:
- [ProfileUpdateTest.php](/C:/xampp/program-of-action-erp/tests/Feature/Settings/ProfileUpdateTest.php:7)

At the same time, adjudication tests show the newer architecture pattern is testable and already partially covered.

Reference:
- [BusinessDevelopmentAdjudicationAssessmentTest.php](/C:/xampp/program-of-action-erp/tests/Feature/BusinessDevelopmentAdjudicationAssessmentTest.php:81)

Implication:

- the suite is not useless,
- but it is no longer a reliable whole-system confidence signal.

## 7. Operational Documentation Is Thin

There is effectively no repo-local project documentation pack yet, and there are no visible first-party Docker or compose artifacts in the repo snapshot.

Implication:

- recovery cost is higher after time away,
- environment assumptions are implicit,
- future domain work should ship with documentation, not only code.

## 8. Existing Best-Practice Template

The best internal template for future work is the Business Development adjudication slice because it already combines:

- domain models,
- repository abstraction,
- service orchestration,
- explicit policy,
- transactional workflow,
- regression tests.

That is the pattern worth reproducing across the other stateful domains.
