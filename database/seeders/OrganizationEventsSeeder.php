<?php

namespace Database\Seeders;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventService;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationEventsSeeder extends Seeder
{
    public function run(): void
    {
        $organization = OrganizationProfile::query()->latest('id')->first();

        $organizationPayload = [
            'name' => 'Programme of Action ERP',
            'legal_name' => 'Programme of Action NPC',
            'tagline' => 'Governance, compliance, programme delivery, and impact reporting in one platform',
            'mission' => 'To strengthen organisational governance, programme delivery, and evidence-based reporting through one integrated operating platform.',
            'vision' => 'A resilient civil society organisation operating with accountable governance, funded delivery, and measurable impact.',
            'objectives' => implode("\n", [
                'Bridge the digital and gender divide through technology, innovation, and business incubation interventions.',
                'Unlock the value and opportunities through creating awareness within the digital creative industries.',
                'Introduce creative industries as a lucrative business opportunity and career.',
                'Leverage on existing ecosystems to sustain start-up entrepreneurs.',
                'Drive research and development in the digital creative sector.',
            ]),
            'focus_areas' => implode("\n", [
                'Incubation',
                'Gaming',
                'Animation',
                'VR & AR',
                'Drone Technology',
            ]),
            'about' => 'Programme of Action ERP supports governance, compliance, programme delivery, stakeholder coordination, and reporting for mission-driven organisations.',
            'service_offering' => 'Governance management, compliance oversight, programme operations, asset coordination, and reporting support.',
            'email' => 'info@programmeofaction.example.com',
            'phone' => '+27 11 000 0000',
            'city' => 'Johannesburg',
            'province' => 'Gauteng',
            'country' => 'South Africa',
            'impact_total' => 947166,
            'impact_digital' => 824088,
            'impact_physical' => 123078,
            'trainings_conducted' => 193,
            'impact_website' => 249408,
            'impact_walkins' => 53464,
            'impact_facebook' => 1671427,
            'impact_x' => 111455,
            'impact_linkedin' => 225147,
            'impact_livestreaming' => 47941,
            'impact_instagram' => 59414,
            'impact_youtube' => 200067,
        ];

        if ($organization) {
            $organization->update($organizationPayload);
        } else {
            OrganizationProfile::query()->create($organizationPayload);
        }

        $user = User::query()->first() ?? User::factory()->create([
            'name' => 'POA Demo User',
            'email' => 'demo@programmeofaction.example.com',
        ]);

        $department = StaffDepartment::query()->firstOrCreate(
            ['name' => 'Business Development'],
            ['description' => 'Business Development']
        );

        $staff = StaffMember::query()->firstOrCreate(
            ['email' => 'manager.events@programmeofaction.example.com'],
            [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'first_name' => 'Event',
                'last_name' => 'Manager',
                'employee_number' => 'EMP-EVT-001',
                'status' => 'active',
                'is_manager' => true,
            ]
        );

        if (! $staff->user_id) {
            $staff->update(['user_id' => $user->id]);
        }

        $sponsor = Stakeholder::query()->firstOrCreate(
            ['email' => 'partnerships@innovationfund.example.com'],
            [
                'organization_name' => 'Innovation Fund SA',
                'name' => 'Innovation Partnerships Office',
                'contact_number' => '+27 11 555 1000',
                'status' => 'active',
            ]
        );

        $partner = Stakeholder::query()->firstOrCreate(
            ['email' => 'programmes@yesforyouth.example.com'],
            [
                'organization_name' => 'YES For Youth',
                'name' => 'Programme Partnerships Team',
                'contact_number' => '+27 11 555 2000',
                'status' => 'active',
            ]
        );

        $event = Event::query()->firstOrCreate(
            ['title' => 'Programme of Action Governance Summit 2026'],
            [
                'event_type' => 'Summit',
                'event_format' => 'hybrid',
                'annual_series_key' => 'programme-of-action-governance-summit',
                'event_year' => 2026,
                'is_annual' => true,
                'theme' => 'Strengthening Governance, Compliance, and Programme Delivery',
                'track_name' => 'Organisation Governance and Impact Delivery',
                'location' => 'Johannesburg',
                'venue_name' => 'Programme of Action Centre',
                'venue_address' => '1 Civic Operations Way, Johannesburg, Gauteng, South Africa',
                'venue_contact_person' => 'Venue Operations Desk',
                'venue_contact_phone' => '+27 11 000 1234',
                'venue_contact_email' => 'venue@programmeofaction.example.com',
                'start_date' => '2026-09-15',
                'end_date' => '2026-09-16',
                'status' => 'open_for_registration',
                'description' => 'A flagship annual event bringing together governance leaders, delivery teams, donors, and ecosystem stakeholders.',
                'objectives' => 'Showcase organisational progress, connect funders and partners, and strengthen governance and compliance delivery.',
                'technical_requirements' => 'Projector, stage microphones, livestream support, registration desk laptops, and stable high-speed internet.',
                'registration_link' => 'https://events.programmeofaction.example.com/governance-summit-2026',
                'zoom_join_url' => 'https://zoom.example.com/j/poa-summit-2026',
                'zoom_host_url' => 'https://zoom.example.com/s/poa-summit-2026-host',
                'zoom_meeting_id' => '817 4455 2026',
                'zoom_passcode' => 'POA2026',
                'expected_attendees' => 250,
                'owner_staff_member_id' => $staff->id,
            ]
        );

        $event->partners()->syncWithoutDetaching([$sponsor->id, $partner->id]);

        collect([
            [
                'category' => 'speaker',
                'name' => 'Dr Lerato Mokoena',
                'title' => 'CEO',
                'organization_name' => 'Innovation Fund SA',
                'topic' => 'Financing Enterprise Growth',
                'bio' => 'Enterprise finance and ecosystem strategy leader.',
                'email' => 'lerato@example.com',
                'phone' => '0710000001',
                'attendance_status' => 'confirmed',
                'sort_order' => 1,
            ],
            [
                'category' => 'speaker',
                'name' => 'Musa Dlamini',
                'title' => 'Programme Director',
                'organization_name' => 'YES For Youth',
                'topic' => 'Youth Enterprise Activation',
                'bio' => 'Programme operator focused on youth pathways and incubation partnerships.',
                'email' => 'musa@example.com',
                'phone' => '0710000002',
                'attendance_status' => 'confirmed',
                'sort_order' => 2,
            ],
            [
                'category' => 'vip',
                'name' => 'John Mabona',
                'email' => 'john@example.com',
                'phone' => '0720000001',
                'organization_name' => 'Programme of Action',
                'role' => 'Host and MC',
                'attendance_status' => 'confirmed',
                'sort_order' => 1,
            ],
            [
                'category' => 'attendee',
                'name' => 'Ayanda Ndlovu',
                'email' => 'ayanda@example.com',
                'phone' => '0720000002',
                'organization_name' => 'Innovation Fund SA',
                'role' => 'Guest Speaker',
                'attendance_status' => 'registered',
                'sort_order' => 2,
            ],
            [
                'category' => 'media_house',
                'name' => 'Creative Voice Media',
                'email' => 'media@example.com',
                'phone' => '0720000003',
                'organization_name' => 'Creative Voice Media',
                'role' => 'Media Partner',
                'attendance_status' => 'confirmed',
                'sort_order' => 3,
            ],
            [
                'category' => 'team_board',
                'name' => 'Programme of Action Board Secretariat',
                'email' => 'board@example.com',
                'phone' => '0720000004',
                'organization_name' => 'Programme of Action',
                'role' => 'Board Coordination',
                'attendance_status' => 'registered',
                'sort_order' => 4,
            ],
        ])->each(function (array $participant) use ($event) {
            $event->participants()->firstOrCreate(
                [
                    'category' => $participant['category'],
                    'name' => $participant['name'],
                ],
                $participant,
            );
        });

        $event->outcomeReport()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'summary' => 'Annual summit delivery report for ecosystem partners, speakers, and institutional stakeholders.',
                'highlights' => 'Hybrid summit delivered with speakers, ecosystem guests, media support, and institutional representation.',
                'opportunities_created' => 'Captured lead opportunities for incubatee support, exposure, and follow-on collaboration.',
                'partnerships_formed' => 'Strengthened collaboration with Innovation Fund SA and YES For Youth for future event and incubation work.',
                'training_opportunities' => 'Documented enterprise support and ecosystem development opportunities raised during the summit.',
                'media_coverage' => 'Media partner content, livestream outputs, and visual coverage archived.',
                'statistics_summary' => 'Participant, attendance, and category-level register data available in the event register outputs.',
                'thank_you_status' => 'Speaker, partner, and guest thank-you communications drafted as part of post-event close-out.',
                'follow_up_actions' => 'Team to close post-event tasks, follow up on opportunities, and finalize event reporting.',
                'report_status' => 'draft',
                'reported_by_staff_member_id' => $staff->id,
                'reported_at' => now(),
            ]
        );

        app(EventService::class)->ensurePlanningTemplate($event->fresh('workstreams.tasks'));
    }
}
