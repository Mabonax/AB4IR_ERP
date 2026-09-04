# AB4IR ERP Training Manual

Version: 2026-09-02

Audience: ERP trainers, programme managers, project managers, facilitators, HR staff, finance staff, marketing staff, document controllers, and system administrators.

Purpose: This manual trains users to operate the AB4IR ERP in the correct business sequence. The most important rule is that upstream records must exist before downstream records can be used properly.

## 1. Training Approach

The ERP should be taught as an operating workflow, not as a list of separate menu items. The recommended training order is:

1. Access Control and user setup.
2. Organization profile and master records.
3. Staff, stakeholders, and facilitators.
4. Programs.
5. Projects.
6. Project locations.
7. Beneficiaries.
8. Project enrollments.
9. Attendance, milestones, progress, and LMS delivery.
10. Documents, vault publication, reports, and closure.
11. Supporting domains: HR, assets, finance, marketing, events, tasks, notifications.

The reason for this order is simple:

- A project cannot be created properly without a program.
- A project should not be operationalized without a manager, sponsor, and delivery structure.
- A project location cannot be useful without a project, facilitator, and province.
- A beneficiary should be captured against the correct project and project location.
- Attendance and progress tracking depend on enrolled beneficiaries.
- LMS provisioning depends on the ERP project, beneficiary, and facilitator identity being correct.
- Reports and closure depend on evidence collected during delivery.

## 2. System Overview

The AB4IR ERP is a Laravel/Inertia operational system with domain modules shown in the application sidebar according to the logged-in user's permissions.

Main sidebar modules:

- Dashboard
- Beneficiaries
- Stakeholders
- Organization
- Facilitators
- Human Resources
- Assets
- Programs
- Projects
- Business Development
- Events
- Task Management
- Marketing Operations
- Finance
- Notifications
- Document Library
- Official Vault
- Delivery Locations
- Access Control

Users only see modules they are allowed to view. If a trainee cannot see a menu item, first check their role and permissions before assuming the feature is missing.

## 3. Core Concepts

### 3.1 Programs

A program is the highest delivery container. It represents a strategic training or delivery stream, for example a digital skills program, entrepreneurship program, or sector-specific intervention.

Programs are created first because projects belong to programs.

Typical program information:

- Title
- Description
- Slug
- Program dashboard metrics
- Related projects
- Program document repository
- Brochures and posters workspace

### 3.2 Projects

A project is the operational delivery unit under a program. It has a start date, status, project manager, sponsor or partner structure, funding reference, reporting cadence, milestones, locations, beneficiaries, attendance, LMS delivery, reports, and finalization.

Projects are created after programs because every project must be linked to a program.

Typical project information:

- Project name
- Program
- Start date and optional end date
- Status: planned, active, completed, on hold, cancelled
- Sponsor stakeholder
- Implementation partners
- Project manager
- Contract reference
- Funding amount
- Reporting cadence
- Reporting obligations
- Description

### 3.3 Project Locations

A project location is where delivery happens. It links a project to a facilitator and a province, with an optional training venue address.

Project locations are created after projects because they are attached to a project.

Typical location information:

- Project
- Facilitator
- Province
- Training venue address

### 3.4 Beneficiaries

A beneficiary is the learner or participant receiving services through a project. The ERP is the system of record for beneficiary identity.

Beneficiaries are captured after the project and project location exist because the beneficiary form requires those selections.

Typical beneficiary information:

- First name
- Surname
- Date of birth
- Age
- ID number
- Email
- Phone number
- Gender
- Project
- Project location
- Address
- Province
- Postal code
- Highest qualification
- Attendance status
- Next of kin details

### 3.5 Project Enrollments

An enrollment links a beneficiary to a project and a location. This is the operational record used to group learners for attendance, progress, and delivery monitoring.

Enrollment status values:

- Enrolled
- Completed
- Dropped

### 3.6 Facilitators

Facilitators deliver project activities at assigned project locations. They are used by project locations, attendance registers, progress tracking, and LMS facilitator provisioning.

### 3.7 Stakeholders

Stakeholders are organizations or contacts involved in the ERP, such as sponsors, partners, implementation organizations, and external contacts.

Stakeholders should be captured before projects when they will be used as sponsors or partners.

### 3.8 Staff

Staff records are used for internal users, project managers, managers, departments, HR, attendance, leave, notifications, and approval workflows.

Project managers should exist as staff members before projects are created.

## 4. User Access and Permissions

### 4.1 Trainer Objective

By the end of this section, trainees must understand that access is role-based and permission-based. A missing menu item usually means the user does not have the required permission.

### 4.2 Access Control Module

Path: Access Control

Subsections:

- Roles
- Permissions
- Assignments

Access Control is normally visible only to super admin users.

### 4.3 How to Demonstrate User Access

1. Sign in as an administrator or super admin.
2. Open Access Control.
3. Show the Roles page.
4. Show the Permissions page.
5. Open Assignments.
6. Select a user.
7. Assign the role or direct permissions needed for their work.
8. Save.
9. Ask the trainee to sign in or refresh the system.
10. Confirm that the expected sidebar modules appear.

### 4.4 Permission Training Notes

Use role access for normal teams. Use direct user permissions only when a specific user needs an exception.

Typical permission groups include:

- domain.programs.view
- domain.programs.manage
- domain.projects.view
- domain.projects.manage
- domain.beneficiaries.view
- domain.beneficiaries.manage
- domain.human-resources.view
- domain.human-resources.manage
- domain.assets.view
- domain.assets.manage
- domain.organization.view
- domain.organization.manage
- domain.marketing.view
- domain.marketing.manage
- domain.task-management.view
- domain.task-management.manage
- domain.finance.view
- domain.finance.manage

## 5. Master Data Setup

Master data must be ready before operational training begins.

### 5.1 Organization Profile

Path: Organization > Organization Profile

Use this module to review the organization's profile information and operating context. This is the reference area for organization-level information and documents.

Training demonstration:

1. Open Organization.
2. Review profile details.
3. Explain that organization information supports governance, reporting, vault documents, and organizational evidence.

### 5.2 Stakeholders

Path: Stakeholders

Stakeholders should be created before projects when they are sponsors or implementation partners.

Required and useful fields:

- Organization
- Stakeholder name
- Email
- Contact number
- Status
- Contact full name
- Contact email
- Contact number
- Position

Training demonstration:

1. Open Stakeholders.
2. Click Add Stakeholder.
3. Capture a sponsor organization.
4. Capture contact person details.
5. Set status to Active.
6. Save.
7. Explain that this stakeholder can now be selected as a sponsor or partner on a project.

### 5.3 Staff and Departments

Path: Human Resources > Staff

Staff records should exist before assigning project managers or configuring HR workflows.

Required and useful fields:

- First name
- Last name
- Email
- Phone
- Employee number
- Start date
- Manager
- CEO flag
- Board member flag
- Department
- Status
- Next of kin details

Training demonstration:

1. Open Human Resources.
2. Open Staff.
3. Add a staff member.
4. Assign a department.
5. Set status to Active.
6. Save.
7. Explain that staff members can be used as project managers and workflow participants.

### 5.4 Facilitators

Path: Facilitators

Facilitators should be created before project locations because locations need a facilitator assignment.

Training demonstration:

1. Open Facilitators.
2. Add or review a facilitator.
3. Confirm the facilitator has correct contact and identity details.
4. Explain that a facilitator can later be assigned to one or more project locations.

## 6. Primary ERP Workflow

This is the most important section of the manual. It demonstrates how the ERP is used from planning to delivery and closure.

## 7. Step 1: Create a Program

Path: Programs

### 7.1 Why Programs Come First

Programs are the strategic container for projects. A project form requires a Program selection, so trainees must not start by creating projects unless the relevant program already exists.

### 7.2 Program Fields

Capture:

- Title
- Description
- Slug

The slug should be URL-friendly. Use lowercase words separated by hyphens, for example `digital-literacy-2026`.

### 7.3 Demonstration: Create a Program

Scenario: AB4IR wants to run a new digital skills training stream.

1. Open Programs.
2. Click Add Program.
3. Enter Title: Digital Skills Programme 2026.
4. Enter Description: A structured programme for digital literacy, workplace readiness, and applied technology skills.
5. Enter Slug: digital-skills-programme-2026.
6. Save.
7. Confirm the program appears on the Programs dashboard.
8. Open the program record.
9. Point out its dashboard, related projects area, and document repository.

### 7.4 Trainer Talking Points

- Do not create duplicate programs for each location.
- Use one program for the strategic stream, then create separate projects underneath it.
- Program dashboards aggregate project performance and impact.
- Program document repositories should store program-level material, not day-to-day project attendance.

### 7.5 Practical Exercise

Ask trainees to create a program using the following sample:

- Title: Entrepreneurship Support Programme 2026
- Description: Training and incubation support for emerging entrepreneurs.
- Slug: entrepreneurship-support-2026

Expected result: The program is visible in the Programs dashboard and can be selected when creating a project.

## 8. Step 2: Create a Project Under the Program

Path: Projects > Projects

### 8.1 Why Projects Come After Programs

Projects must belong to a program. A project is where funding, timeframes, project managers, sponsors, locations, beneficiaries, attendance, progress, LMS delivery, and closure are managed.

### 8.2 Project Fields

Capture:

- Project Name
- Start Date
- End Date if known
- Status
- Program
- Sponsor
- Implementation Partners
- Project Manager
- Contract Reference
- Funding Amount
- Reporting Cadence
- Reporting Obligations
- Description

### 8.3 Demonstration: Create a Project

Scenario: Create a project under Digital Skills Programme 2026.

1. Open Projects.
2. Open the Projects tab.
3. Click Add Project.
4. Enter Project Name: Digital Skills Cohort Gauteng 2026.
5. Select Start Date.
6. Select End Date if known.
7. Set Status to Planned or Active.
8. Select Program: Digital Skills Programme 2026.
9. Select the Sponsor stakeholder if available.
10. Select implementation partners if applicable.
11. Select Project Manager.
12. Enter Contract Reference if available.
13. Enter Funding Amount if available.
14. Select Reporting Cadence, for example Monthly.
15. Add Reporting Obligations.
16. Add a Description.
17. Save.
18. Confirm the project opens on its detail page.

### 8.4 Project Detail Page

The project detail page is the main working area for a project.

Use it to review:

- Project summary
- Program link
- Project manager
- Locations
- Beneficiary and enrollment information
- Milestones
- Attendance trend
- LMS delivery workspace
- Project history
- Document repository
- Brochure repository
- Finalization link

### 8.5 Trainer Talking Points

- Projects should not be used as generic categories. They represent a real delivery contract or operational initiative.
- A project needs a manager before it can be governed properly.
- Use statuses consistently:
  - Planned: approved but not yet delivering.
  - Active: delivery is running.
  - Completed: delivery is finished.
  - On Hold: temporarily paused.
  - Cancelled: stopped before completion.
- The reporting cadence and obligations should reflect sponsor or management requirements.

### 8.6 Practical Exercise

Ask trainees to create a project:

- Program: Entrepreneurship Support Programme 2026
- Project Name: Entrepreneurship Cohort Limpopo 2026
- Status: Planned
- Reporting Cadence: Monthly
- Project Manager: Select an available staff member

Expected result: The project exists under the selected program and can be used when creating locations and beneficiaries.

## 9. Step 3: Create Project Locations

Path: Projects > Locations

### 9.1 Why Locations Come After Projects

A location belongs to a project. Attendance, facilitator access, progress tracking, and beneficiary grouping depend on the project location.

### 9.2 Location Fields

Capture:

- Project
- Facilitator
- Province
- Training Venue Address

### 9.3 Demonstration: Create a Project Location

Scenario: Add a Gauteng delivery location to Digital Skills Cohort Gauteng 2026.

1. Open Projects.
2. Open Locations.
3. Click Add Project Location.
4. Select Project: Digital Skills Cohort Gauteng 2026.
5. Select Facilitator.
6. Select Province: Gauteng.
7. Enter Training Venue Address.
8. Save.
9. Confirm the location appears in the locations list.

### 9.4 Delivery Locations Dashboard

Path: Delivery Locations

The Delivery Locations dashboard is useful for facilitators and delivery managers. It shows assigned locations, beneficiary counts, milestone progress, and assessment completion.

Facilitators may see only locations assigned to them, depending on access rules.

### 9.5 Trainer Talking Points

- Create one location for each delivery site or province assignment.
- Assign the correct facilitator before delivery starts.
- Use the training venue address to help with attendance, logistics, and reporting.
- A beneficiary should be linked to the correct project location so attendance and progress are accurate.

### 9.6 Practical Exercise

Ask trainees to create two locations under the same project:

- Location 1: Gauteng, Facilitator A
- Location 2: Limpopo, Facilitator B

Expected result: The project has multiple delivery locations and each location can hold its own beneficiary group and attendance register.

## 10. Step 4: Capture Beneficiaries

Path: Beneficiaries

### 10.1 Why Beneficiaries Come After Projects and Locations

The beneficiary form requires a Project and Project Location. If those do not exist, the user cannot correctly place the beneficiary into delivery.

This ERP treats beneficiary identity as authoritative. Do not create duplicate beneficiary records when updating a learner's project participation. Use transfer, lifecycle, and enrollment features where appropriate.

### 10.2 Beneficiary Fields

Capture:

- First Name
- Surname
- Date of Birth
- Age
- ID Number
- Email
- Phone Number
- Gender
- Project
- Project Location
- Street Address
- Address Line 2
- City
- Province
- Postal Code
- Highest Qualification
- Attendance Status
- Next of Kin Name
- Next of Kin Surname
- Relationship
- Next of Kin Phone
- Next of Kin Email

### 10.3 Demonstration: Add a Beneficiary

Scenario: Add a learner to Digital Skills Cohort Gauteng 2026.

1. Open Beneficiaries.
2. Click Add Beneficiary.
3. Capture personal details.
4. Capture ID number carefully.
5. Capture email carefully because LMS invitations and access may depend on it.
6. Select Project: Digital Skills Cohort Gauteng 2026.
7. Select Project Location: Gauteng location.
8. Set Attendance Status to Active.
9. Capture next of kin details.
10. Save.
11. Confirm the beneficiary detail page opens.

### 10.4 Filtering Beneficiaries

On the Beneficiaries page:

1. Filter by Program.
2. Filter by Project.
3. Review the selected project summary.
4. Review lifecycle metrics where available.

This helps trainers show that beneficiaries are not just a flat list. They belong to operational cohorts.

### 10.5 Importing Beneficiaries

Path: Beneficiaries > Import action

Use import when capturing a group of beneficiaries from a file.

Import requires:

- Import file
- Project
- Project location

The system reports:

- Processed rows
- Created records
- Matched existing records
- Rejected duplicates
- Errors

Trainer warning: Always import into the correct project and project location. A wrong import creates operational errors in attendance, LMS provisioning, and reports.

### 10.6 Beneficiary Lifecycle Actions

The beneficiary detail page supports lifecycle actions where permissions allow:

- Suspend
- Reinstate
- Graduate
- Exit
- Transfer
- Archive
- Provision LMS access
- Resend LMS invitation

Use lifecycle actions instead of editing history informally.

### 10.7 Practical Exercise

Ask trainees to capture three beneficiaries:

1. One active beneficiary in Gauteng.
2. One active beneficiary in Limpopo.
3. One beneficiary imported or captured with complete next of kin details.

Expected result: The beneficiaries appear under the correct project and location filters.

## 11. Step 5: Manage Project Enrollments

Path: Projects > Enrollments

### 11.1 What Enrollment Does

Enrollment confirms that a beneficiary is part of a project and project location. It is the operational link used for group delivery.

Enrollment fields:

- Project
- Project Location
- Beneficiary
- Status
- Enrollment Date

### 11.2 Demonstration: Enroll a Beneficiary

1. Open Projects.
2. Open Enrollments.
3. Click Enroll Beneficiary.
4. Select Project.
5. Select Project Location.
6. Select Beneficiary.
7. Set Status to Enrolled.
8. Set Enrollment Date.
9. Save.
10. Confirm the beneficiary count updates for the project location.

### 11.3 Trainer Talking Points

- Enrollment should match the beneficiary's real delivery location.
- Do not enroll a learner into a location they are not attending.
- Use Completed when a beneficiary completes the project.
- Use Dropped when a beneficiary drops from the project.
- Lifecycle status and enrollment status must be kept consistent.

### 11.4 Practical Exercise

Ask trainees to enroll two beneficiaries into one project location and one beneficiary into another project location.

Expected result: The Enrollments page groups beneficiaries by project and location.

## 12. Step 6: Track Attendance

Paths:

- Projects > Attendance
- Project Locations > Attendance
- Delivery Locations

### 12.1 Attendance Dependencies

Attendance depends on:

- Project
- Project location
- Facilitator
- Enrolled beneficiaries
- Active beneficiary attendance status

Only beneficiaries who are active and not marked as dropout should appear in a normal attendance register.

### 12.2 Demonstration: Capture Attendance for a Location

1. Open Delivery Locations or Projects > Locations.
2. Open a project location.
3. Open Attendance.
4. Select the attendance date.
5. Review the beneficiary list.
6. Mark each beneficiary:
   - Present
   - Absent
   - Excused
7. If Excused is selected, capture the reason.
8. Save the register.
9. Confirm the day statistics update.

### 12.3 Mark a Holiday

1. Open the location attendance page.
2. Select the date.
3. Choose the holiday option if available.
4. Capture the holiday reason.
5. Save.

Holiday days are kept separate from normal attendance days.

### 12.4 Export Attendance Register

When a register exists:

1. Open the relevant attendance register.
2. Export or download PDF where available.
3. Use the PDF for evidence, sponsor reporting, or project closure records.

### 12.5 Practical Exercise

Ask trainees to:

1. Capture attendance for today's date.
2. Mark one beneficiary Present.
3. Mark one beneficiary Absent.
4. Mark one beneficiary Excused with a reason.
5. Save.
6. Review the attendance history.

Expected result: The location attendance history shows the saved register and day statistics.

## 13. Step 7: Manage Milestones and Progress

Paths:

- Projects > Milestones
- Project detail page
- Project Locations > Progress

### 13.1 Milestone Concept

Milestones represent delivery progress points or assessment steps for a project. They can be attached to a project from program milestone templates.

### 13.2 Demonstration: Sync Program Milestones to a Project

1. Open Projects.
2. Open the target project.
3. Find the milestones section.
4. Use Sync Program Milestones where available.
5. Confirm milestones appear on the project.

### 13.3 Demonstration: Track Location Progress

1. Open Delivery Locations.
2. Select a location.
3. Open Progress.
4. Review milestones.
5. Select a beneficiary.
6. Capture assessment information where available.
7. Confirm completed assessments update.

### 13.4 Trainer Talking Points

- Milestones should be defined before detailed progress reporting.
- Location progress is useful for facilitators and project managers.
- Assessment completion is not the same as attendance. Both must be captured when required.

## 14. Step 8: LMS Learning Delivery

Path: Project detail page

The ERP integrates with the LMS for learning delivery. The ERP remains the system of record for beneficiaries, facilitators, and project identity. The LMS handles learning-specific delivery state.

### 14.1 LMS Workflow

Recommended order:

1. Create program.
2. Create project.
3. Create project locations.
4. Capture facilitators.
5. Capture beneficiaries.
6. Enroll beneficiaries.
7. Open the project detail page.
8. Review available LMS offerings.
9. Map the project to an LMS offering.
10. Provision learners.
11. Provision facilitators.
12. Assign facilitator to teaching delivery where applicable.
13. Review learning summary.

### 14.2 Demonstration: Map a Project to an LMS Offering

1. Open Projects.
2. Open the target project.
3. Find the Learning Delivery area.
4. Review available LMS offerings.
5. Select the correct LMS offering.
6. Save or map the offering.
7. Confirm the mapping result.

### 14.3 Demonstration: Provision Learners

1. Stay on the project detail page.
2. Find learner provisioning.
3. Select beneficiaries.
4. Submit provisioning.
5. Review accepted and rejected results.
6. Resolve any rejected beneficiaries, especially missing or duplicate identity details.

### 14.4 Demonstration: Provision Facilitators

1. Stay on the project detail page.
2. Find facilitator provisioning.
3. Select facilitators.
4. Submit provisioning.
5. Review the result.

### 14.5 Beneficiary LMS Access

On a beneficiary detail page, authorized users may:

- Provision LMS access for that beneficiary.
- Resend LMS invitation.
- Review learning summary.
- Suspend or reactivate LMS access when lifecycle actions apply.

### 14.6 Trainer Warnings

- Do not duplicate beneficiaries in the LMS manually when ERP provisioning is used.
- Resolve invalid or duplicate emails before provisioning.
- Provision only beneficiaries who belong to the correct project.
- LMS actions can fail if the LMS is unavailable or the learner is not eligible.

## 15. Step 9: Documents and Evidence

Paths:

- Document Library
- Official Vault
- Organization > Working Library
- Organization > Official Vault
- Program detail page
- Project detail page

### 15.1 Working Library vs Official Vault

The Working Library is for operational document management, folder structures, drafts, project files, program files, beneficiary-related files, and internal collaboration.

The Official Vault is for approved or official organization documents.

### 15.2 Program and Project Repositories

Program records can have a document repository and a Brochures & Posters folder.

Project records can have a document repository and a Brochures folder.

Use contextual brochure upload from the Program or Project page when the document belongs to that program or project.

### 15.3 Demonstration: Use a Project Document Repository

1. Open Projects.
2. Open the target project.
3. Find the Document Repository link.
4. Open the repository.
5. Create or open the relevant folder.
6. Upload a document.
7. Rename, move, check in, or version documents as required.
8. Publish to the Official Vault only when the document is approved and official.

### 15.4 Demonstration: Upload a Brochure to the Project Brochure Folder

1. Open the project detail page.
2. Find the brochure repository area.
3. Upload the brochure.
4. Confirm the file appears in the brochure folder.
5. If the user has permission, publish the brochure to the Official Vault.

### 15.5 Evidence Good Practice

- Store attendance PDFs with the project or closure evidence.
- Store sponsor reports in project reports or document repositories.
- Store approved brochures in the official vault.
- Avoid uploading the same document repeatedly in unrelated folders.
- Use clear file names that include project, document type, and date.

## 16. Step 10: Project Reporting and Closure

Path: Project detail page > Finalization

### 16.1 When to Use Finalization

Use finalization when the project delivery period is complete and the project requires closure evidence, final report generation, and signoff notes.

### 16.2 Closure Inputs

Finalization can include:

- Closure date
- Signoff notes
- Final report summary
- Report title
- Key findings
- Recommendations
- Closure evidence uploads
- Progress reports
- Final reports

### 16.3 Demonstration: Create a Progress Report

1. Open Projects.
2. Open the target project.
3. Open Finalization.
4. Choose Create Report.
5. Select Report Type: Progress.
6. Enter report date.
7. Add executive summary.
8. Add key findings.
9. Add recommendations.
10. Save.
11. Download the report PDF if needed.

### 16.4 Demonstration: Upload Closure Evidence

1. Open the project finalization page.
2. Upload evidence.
3. Select a category such as evidence or registers.
4. Enter title and notes.
5. Select file.
6. Save.
7. Confirm evidence appears in the closure evidence list.

### 16.5 Demonstration: Conclude a Project

1. Confirm attendance, milestones, documents, and reports are complete.
2. Open Finalization.
3. Enter closure date.
4. Enter signoff notes.
5. Enter final report summary.
6. Add findings and recommendations.
7. Submit conclusion.
8. Confirm the project is marked as concluded.

### 16.6 Closure Checklist

Before concluding a project, confirm:

- Program is correct.
- Project manager is correct.
- Sponsor and partners are correct.
- Locations are complete.
- Beneficiaries are correctly captured.
- Enrollments are accurate.
- Attendance registers are saved.
- Milestones and assessments are complete where required.
- LMS delivery status has been reviewed.
- Required documents are uploaded.
- Closure evidence is uploaded.
- Progress or final reports are generated.

## 17. Supporting Module Training

## 18. Human Resources

Path: Human Resources

Subsections:

- HR Dashboard
- Staff
- Leave Management
- Attendance

### 18.1 HR Dashboard

Use the HR dashboard to review staff, departments, attendance, leave, workforce analytics, holidays, and calendar information.

### 18.2 Leave Management

Path: Human Resources > Leave Management

Workflow:

1. Staff submit leave request.
2. Manager approves or rejects.
3. HR approves or rejects.
4. Documents can be uploaded where required.
5. Approved leave is tracked in HR views.

### 18.3 Staff Attendance

Path: Human Resources > Attendance

Use this for internal staff attendance management, reports, and late clock-in override handling.

Trainer distinction: Staff attendance is not the same as project beneficiary attendance.

## 19. Assets

Path: Assets

Subsections:

- Portfolio Summary
- Assets List
- Asset Register
- Manager Analytics
- Categories

### 19.1 Asset Capture

Typical asset fields:

- Asset Name
- Category
- Type
- Model
- Serial Number
- Serial State
- Status

### 19.2 Asset Workflow

Use Assets to:

- Create asset categories.
- Register assets.
- Assign assets.
- Return assets.
- Start maintenance.
- Complete maintenance.
- Report faults.
- Decommission assets.
- Export the asset register.

Training demonstration:

1. Create an asset category.
2. Add an asset.
3. Assign it.
4. Return it.
5. Start and complete maintenance.

## 20. Finance

Path: Finance

Subsections:

- Travel Claims
- New Claim

### 20.1 Travel Claim Workflow

1. User creates a travel claim.
2. Claim is reviewed.
3. Authorized user approves or rejects.
4. Finance receives the claim.
5. Finance pays or rejects the claim.
6. Claim PDF can be downloaded.

Trainer distinction: Finance permissions may allow users to submit claims without having full finance management access.

## 21. Marketing Operations

Path: Marketing Operations

Subsections:

- Dashboard
- Requests
- Deliverables
- Approvals
- Assets
- Publications

### 21.1 Marketing Workflow

Use Marketing Operations to manage:

- Marketing requests
- Jobs
- Comments
- Documents
- Deliverable versions
- Approval or amendment requests
- Marketing assets
- Publication records
- Imported publication metrics
- Publishing approved assets to the vault

Training demonstration:

1. Create a marketing request.
2. Upload supporting documents.
3. Create or review deliverables.
4. Submit for approval.
5. Approve or request changes.
6. Publish approved assets where appropriate.

## 22. Task Management

Path: Task Management

Subsections:

- Dashboard
- Tasks
- Support Tickets

### 22.1 Work Task Workflow

Use Tasks for internal work assignments.

Workflow:

1. Create task.
2. Assign responsible user.
3. Update status.
4. Add comments.
5. Upload documents or proof.
6. Submit for review.
7. Manager approves, finalizes, or returns for amendments.
8. Download proof or preview documents when needed.

### 22.2 Support Ticket Workflow

Use Support Tickets for help requests and internal support.

Workflow:

1. User creates ticket.
2. Support assigns ticket.
3. Support replies.
4. Ticket is resolved.
5. Ticket is closed or reopened if needed.

## 23. Events

Path: Events

Use Events for organization events, planning, workstreams, event tasks, participants, attendance, assets, closure, and reports.

Training demonstration:

1. Create an event.
2. Add workstreams.
3. Add planning tasks.
4. Upload task attachments or evidence.
5. Approve or return event tasks.
6. Track participants and attendance.
7. Complete closure and reporting.

Note: Event functionality may include series and task attachment features depending on the current deployed version.

## 24. Business Development

Path: Business Development

Subsections:

- Dashboard
- Applications
- Incubatees
- Pitch Sessions
- Adjudications

### 24.1 Business Development Workflow

Use Business Development to manage entrepreneurship or incubator pipelines.

Workflow:

1. Review applications.
2. Create or update incubatee records.
3. Schedule pitch sessions.
4. Add panelists and prospects.
5. Complete adjudications.
6. Track incubatee KPI information.

Trainer distinction: Beneficiaries and incubatees are not always the same operational object. Use the module that matches the business process.

## 25. Notifications

Path: Notifications

Notifications inform users about assigned work, approvals, lifecycle changes, and system workflow events.

Training demonstration:

1. Open Notifications.
2. Review unread items.
3. Open a notification.
4. Mark a notification as read.
5. Use Mark All Read when appropriate.

## 26. Recommended Full Training Demonstration

Use this as the main practical demonstration for a class.

### 26.1 Scenario

AB4IR is launching a new training project under a digital skills program. The project will run in Gauteng and Limpopo, with separate facilitators. Beneficiaries will be captured, enrolled, attendance will be recorded, LMS access will be provisioned, documents will be uploaded, and the project will be reported.

### 26.2 Trainer Preparation

Before the session, make sure these exist:

- A super admin or training admin user.
- At least one active staff member to use as project manager.
- At least one active stakeholder to use as sponsor.
- Two facilitators.
- Provinces are seeded.
- The LMS integration is configured if LMS demonstration is required.
- Sample beneficiary data is ready.
- Sample PDF or document file is ready for upload.

### 26.3 Demonstration Script

1. Sign in.
2. Explain the sidebar and permissions.
3. Open Access Control and show roles, permissions, and assignments.
4. Open Organization and review the profile.
5. Create or review a sponsor stakeholder.
6. Create or review staff and project manager.
7. Create or review facilitators.
8. Create Program: Digital Skills Programme 2026.
9. Create Project: Digital Skills Cohort Gauteng and Limpopo 2026.
10. Create Project Location: Gauteng with Facilitator A.
11. Create Project Location: Limpopo with Facilitator B.
12. Create Beneficiary 1 in Gauteng.
13. Create Beneficiary 2 in Limpopo.
14. Open Projects > Enrollments and confirm or create enrollments.
15. Open Delivery Locations and show each location's beneficiary count.
16. Open location attendance and save a daily register.
17. Open project progress and review milestone structure.
18. Open project detail and map LMS offering if configured.
19. Provision learners.
20. Provision facilitators.
21. Upload a project document.
22. Upload or publish a brochure if relevant.
23. Create a progress report.
24. Upload closure evidence.
25. Explain the final conclusion workflow.
26. Open Notifications and show generated workflow messages if available.

### 26.4 Expected Learning Outcome

After this demonstration, trainees should understand:

- Why programs come before projects.
- Why projects and locations come before beneficiaries.
- Why beneficiaries must be correctly linked before attendance and LMS provisioning.
- Where documents and evidence are stored.
- How reports and closure depend on captured operational data.
- How permissions affect what each user can see and do.

## 27. Common Mistakes and Corrections

### 27.1 Creating Projects Before Programs

Problem: The project cannot be correctly categorized.

Correction: Create the program first, then create the project under it.

### 27.2 Creating Beneficiaries Before Locations

Problem: The beneficiary cannot be placed into the correct delivery location.

Correction: Create project locations before capturing beneficiaries or importing beneficiary files.

### 27.3 Selecting the Wrong Project Location

Problem: Attendance and progress reports will show the learner under the wrong facilitator or province.

Correction: Edit or transfer the beneficiary using the correct lifecycle or transfer workflow. Review enrollments.

### 27.4 Duplicating Beneficiaries

Problem: Duplicate identity records create LMS provisioning and reporting errors.

Correction: Search existing beneficiaries before creating new ones. Use lifecycle, transfer, and enrollment workflows.

### 27.5 Forgetting Facilitator Assignment

Problem: Location access, attendance, and LMS facilitator provisioning may fail or be incomplete.

Correction: Assign a facilitator when creating the project location.

### 27.6 Treating Attendance as Progress

Problem: A learner may attend sessions but still not complete milestones.

Correction: Capture attendance and milestone progress separately.

### 27.7 Uploading Official Documents to the Wrong Place

Problem: Approved documents may be hidden in working folders or duplicated.

Correction: Use the Working Library for working files and the Official Vault for approved organizational documents.

### 27.8 Ignoring Rejected LMS Provisioning Results

Problem: Some learners or facilitators do not receive LMS access.

Correction: Read provisioning results, fix rejected identity or eligibility issues, then retry.

## 28. Role-Based Training Paths

### 28.1 System Administrator

Must know:

- Access Control
- User role assignment
- Organization profile
- Permissions
- Notifications
- Document Library and Official Vault
- Troubleshooting visibility issues

### 28.2 Program Manager

Must know:

- Programs
- Projects
- Project reports
- Project finalization
- Stakeholders
- Documents
- Dashboards

### 28.3 Project Manager

Must know:

- Projects
- Locations
- Beneficiaries
- Enrollments
- Attendance summary
- Milestones
- LMS delivery
- Reports
- Closure evidence

### 28.4 Facilitator

Must know:

- Delivery Locations
- Location attendance
- Location progress
- Assigned beneficiaries
- Evidence expectations

### 28.5 HR User

Must know:

- HR Dashboard
- Staff
- Staff attendance
- Leave management
- Staff documents where applicable

### 28.6 Finance User

Must know:

- Travel claims
- Claim submission
- Approval, receipt, payment, and rejection flow
- Claim PDF download

### 28.7 Marketing User

Must know:

- Requests
- Jobs
- Deliverables
- Approvals
- Assets
- Publications
- Vault publishing

### 28.8 Document Controller

Must know:

- Working Library
- Folder creation
- Uploads
- Versioning
- Publishing to Official Vault
- Program and project repositories

## 29. Data Quality Checklist

Use this checklist during training and live operations.

Programs:

- Title is clear.
- Description explains scope.
- Slug is unique and URL-friendly.

Projects:

- Correct program selected.
- Correct project manager selected.
- Sponsor and partners are correct.
- Start date and status are correct.
- Reporting obligations are captured.

Locations:

- Correct project selected.
- Correct facilitator selected.
- Correct province selected.
- Venue address captured where known.

Beneficiaries:

- First name and surname are correct.
- ID number is correct.
- Email is valid and not duplicated improperly.
- Project is correct.
- Project location is correct.
- Attendance status is correct.
- Next of kin is captured.

Enrollments:

- Beneficiary is enrolled into the correct project.
- Beneficiary is enrolled into the correct location.
- Status reflects the real state.
- Enrollment date is correct.

Attendance:

- Correct date selected.
- Correct location selected.
- All active learners marked.
- Excused reasons captured.
- Holiday reason captured where applicable.

Documents:

- File is uploaded to the correct folder.
- File name is meaningful.
- Official documents are published to the Official Vault only when approved.

Reports and Closure:

- Evidence exists.
- Attendance and progress were reviewed.
- Final report summary is accurate.
- Closure date and signoff notes are captured.

## 30. Trainer Assessment

At the end of training, each trainee should complete the following supervised task:

1. Create a program.
2. Create a project under that program.
3. Create one project location.
4. Capture one beneficiary linked to that project and location.
5. Enroll the beneficiary.
6. Capture one attendance register.
7. Upload one project document.
8. Create one progress report or explain where it is created.
9. Explain how LMS provisioning would be done.
10. Explain when the project can be concluded.

Pass criteria:

- The trainee follows the correct sequence.
- The trainee uses the correct module paths.
- The trainee selects the correct project and location relationships.
- The trainee can explain why each dependency exists.
- The trainee does not create duplicate records.

## 31. Quick Reference: Correct Operating Sequence

Use this one-page sequence during live training.

1. Access Control: assign the user the correct role and permissions.
2. Organization: confirm organization profile and document governance.
3. Stakeholders: create sponsors and partners.
4. Human Resources: create staff and project managers.
5. Facilitators: create facilitators.
6. Programs: create the program.
7. Projects: create the project under the program.
8. Project Locations: create locations under the project and assign facilitators.
9. Beneficiaries: capture or import beneficiaries against the correct project and location.
10. Enrollments: confirm beneficiaries are enrolled into the correct project and location.
11. Attendance: capture daily attendance by project location.
12. Milestones and Progress: sync milestones and record assessments.
13. LMS Delivery: map offering, provision learners, provision facilitators.
14. Document Library: upload working files and evidence.
15. Official Vault: publish approved official documents.
16. Reports: create progress and final reports.
17. Finalization: upload closure evidence and conclude the project.
18. Notifications: monitor actions requiring attention.

## 32. Quick Reference: Module Paths

- Dashboard: `/dashboard`
- Beneficiaries: `/beneficiaries`
- Stakeholders: `/stakeholders`
- Organization Profile: `/organization`
- Official Vault: `/organization/documents`
- Working Library: `/organization/document-library`
- Facilitators: `/facilitators`
- Human Resources Dashboard: `/human-resources`
- Staff: `/staff`
- Leave Management: `/leave-requests`
- Staff Attendance: `/human-resources/attendance`
- Assets: `/assets`
- Asset Register: `/assets/register`
- Programs: `/programs`
- Projects Overview: `/projects`
- Projects List: `/projects/list`
- Project Locations: `/project-locations`
- Project Enrollments: `/project-enrollments`
- Project Attendance Summary: `/projects/attendance-summary`
- Milestone Templates: `/milestone-templates`
- Delivery Locations Dashboard: `/project-locations/dashboard`
- Business Development: `/business-development`
- Events: `/events`
- Task Management: `/task-management`
- Tasks: `/task-management/tasks`
- Support Tickets: `/task-management/tickets`
- Marketing Operations: `/marketing`
- Marketing Requests: `/marketing/requests`
- Marketing Deliverables: `/marketing/deliverables/workspace`
- Marketing Approvals: `/marketing/approvals`
- Marketing Assets: `/marketing/assets`
- Marketing Publications: `/marketing/publications`
- Finance Travel Claims: `/finance/travel-claims`
- New Travel Claim: `/finance/travel-claims/create`
- Notifications: `/notifications`
- Access Control Roles: `/access-control/roles`
- Access Control Permissions: `/access-control/permissions`
- Access Control Assignments: `/access-control/assignments`

## 33. Closing Guidance for Trainers

Train users to think in relationships:

- Program owns projects.
- Project owns locations, milestones, reports, evidence, and LMS mappings.
- Location groups beneficiaries and attendance.
- Beneficiary identity must remain clean because it affects reporting and LMS access.
- Documents support evidence and governance.
- Permissions control visibility and action.

The most successful ERP users follow the sequence, keep data clean, and use lifecycle actions instead of informal edits.
