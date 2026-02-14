<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class BdsDashboardController extends Controller
{
    public function __invoke()
    {
        $applications = BdsApplication::with(['assessor', 'updatedBy'])
            ->latest()
            ->get();
        $incubatees = BdsIncubatee::latest()->get();

        $activities = collect();

        foreach ($applications as $application) {
            $activities->push([
                'type' => 'application_submitted',
                'title' => 'Application added',
                'entity' => $application->full_name,
                'entity_type' => 'application',
                'entity_id' => $application->id,
                'status' => $application->assessment_status,
                'details' => $application->company_name,
                'actor' => null,
                'occurred_at' => optional($application->created_at)?->toDateTimeString(),
                'sort_at' => optional($application->created_at)?->timestamp ?? 0,
            ]);

            if ($application->assessed_at) {
                $assessorName = $application->assessor
                    ? trim(($application->assessor->first_name ?? '').' '.($application->assessor->last_name ?? ''))
                    : ($application->updatedBy?->name ?? null);

                $activities->push([
                    'type' => 'application_assessed',
                    'title' => 'Application assessed',
                    'entity' => $application->full_name,
                    'entity_type' => 'application',
                    'entity_id' => $application->id,
                    'status' => $application->assessment_status,
                    'details' => 'Assessment completed',
                    'actor' => $assessorName,
                    'occurred_at' => optional($application->assessed_at)?->toDateTimeString(),
                    'sort_at' => optional($application->assessed_at)?->timestamp ?? 0,
                ]);
            }

            if ($application->pitch_scheduled_at) {
                $activities->push([
                    'type' => 'pitch_scheduled',
                    'title' => 'Pitch scheduled',
                    'entity' => $application->full_name,
                    'entity_type' => 'application',
                    'entity_id' => $application->id,
                    'status' => $application->assessment_status,
                    'details' => 'Pitch date: '.$application->pitch_scheduled_at->toDateTimeString(),
                    'actor' => $application->updatedBy?->name,
                    'occurred_at' => optional($application->updated_at)?->toDateTimeString(),
                    'sort_at' => optional($application->updated_at)?->timestamp ?? 0,
                ]);
            }
        }

        foreach ($incubatees as $incubatee) {
            $activities->push([
                'type' => 'incubatee_created',
                'title' => 'Incubatee added',
                'entity' => $incubatee->full_name,
                'entity_type' => 'incubatee',
                'entity_id' => $incubatee->id,
                'status' => $incubatee->status,
                'details' => $incubatee->company_name,
                'actor' => null,
                'occurred_at' => optional($incubatee->created_at)?->toDateTimeString(),
                'sort_at' => optional($incubatee->created_at)?->timestamp ?? 0,
            ]);

            if ($incubatee->updated_at && $incubatee->updated_at->ne($incubatee->created_at)) {
                $activities->push([
                    'type' => 'incubatee_updated',
                    'title' => 'Incubatee updated',
                    'entity' => $incubatee->full_name,
                    'entity_type' => 'incubatee',
                    'entity_id' => $incubatee->id,
                    'status' => $incubatee->status,
                    'details' => 'Record updated',
                    'actor' => null,
                    'occurred_at' => optional($incubatee->updated_at)?->toDateTimeString(),
                    'sort_at' => optional($incubatee->updated_at)?->timestamp ?? 0,
                ]);
            }
        }

        $activityRows = $activities
            ->sortByDesc('sort_at')
            ->take(200)
            ->values()
            ->map(function (array $row) {
                unset($row['sort_at']);

                return $row;
            });

        return Inertia::render('BusinessDevelopment/Dashboard', [
            'stats' => [
                'totalApplications' => BdsApplication::count(),
                'pendingApplications' => BdsApplication::where('assessment_status', 'pending')->count(),
                'acceptedApplications' => BdsApplication::where('assessment_status', 'accepted')->count(),
                'rejectedApplications' => BdsApplication::where('assessment_status', 'rejected')->count(),
                'scheduledPitches' => BdsApplication::whereNotNull('pitch_scheduled_at')->count(),
                'totalIncubatees' => BdsIncubatee::count(),
                'activeIncubatees' => BdsIncubatee::where('status', 'active')->count(),
                'inactiveIncubatees' => BdsIncubatee::where('status', 'inactive')->count(),
            ],
            'activities' => $activityRows,
        ]);
    }
}

