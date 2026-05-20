<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('task_group')->nullable()->after('phase');
            $table->boolean('is_custom')->default(false)->after('task_group');
        });

        DB::table('event_workstreams')
            ->where('name', 'Marketing and Communications')
            ->update(['name' => 'Marketing']);

        DB::table('event_workstreams')
            ->where('name', 'Media and Technical')
            ->update(['name' => 'Technical']);

        DB::table('event_workstreams')
            ->where('name', 'Impact and Reporting')
            ->update(['name' => 'Impact & Reporting']);

        $tasks = DB::table('event_tasks')
            ->join('event_workstreams', 'event_workstreams.id', '=', 'event_tasks.event_workstream_id')
            ->select('event_tasks.id', 'event_tasks.duty', 'event_workstreams.name as workstream_name')
            ->get();

        foreach ($tasks as $task) {
            DB::table('event_tasks')
                ->where('id', $task->id)
                ->update([
                    'task_group' => $this->inferTaskGroup($task->workstream_name, $task->duty),
                    'is_custom' => false,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('event_workstreams')
            ->where('name', 'Marketing')
            ->update(['name' => 'Marketing and Communications']);

        DB::table('event_workstreams')
            ->where('name', 'Technical')
            ->update(['name' => 'Media and Technical']);

        DB::table('event_workstreams')
            ->where('name', 'Impact & Reporting')
            ->update(['name' => 'Impact and Reporting']);

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn(['task_group', 'is_custom']);
        });
    }

    private function inferTaskGroup(string $workstreamName, string $duty): string
    {
        $department = strtolower($workstreamName);
        $task = strtolower($duty);

        if (str_contains($department, 'admin')) {
            return match (true) {
                str_contains($task, 'venue') || str_contains($task, 'holding room') => 'Venue',
                str_contains($task, 'speaker') => 'Speakers',
                str_contains($task, 'attendee') || str_contains($task, 'register') || str_contains($task, 'participant') || str_contains($task, 'registration') => 'Participants',
                str_contains($task, 'joc') || str_contains($task, 'voc') || str_contains($task, 'insurance') || str_contains($task, 'ems') || str_contains($task, 'officer') || str_contains($task, 'characterisation') => 'JOC / Compliance',
                str_contains($task, 'transport') || str_contains($task, 'accommodation') => 'Transport / Accommodation',
                default => 'General Logistics',
            };
        }

        if (str_contains($department, 'marketing')) {
            return match (true) {
                str_contains($task, 'poster') || str_contains($task, 'signature') || str_contains($task, 'letterhead') || str_contains($task, 'slides') || str_contains($task, 'programme') || str_contains($task, 'design') => 'Graphic Design',
                str_contains($task, 'speaker') || str_contains($task, 'exhibitor') || str_contains($task, 'media') || str_contains($task, 'sponsor') || str_contains($task, 'partner') || str_contains($task, 'invitation') => 'Outreach & Stakeholder Communication',
                default => 'Content & Communications',
            };
        }

        if (str_contains($department, 'technical')) {
            return match (true) {
                str_contains($task, 'zoom') || str_contains($task, 'virtual') || str_contains($task, 'livestream') || str_contains($task, 'live stream') || str_contains($task, 'dry run') => 'Streaming & Virtual Access',
                str_contains($task, 'quotation') || str_contains($task, 'equipment') || str_contains($task, 'microphone') || str_contains($task, 'av') => 'AV / Equipment',
                str_contains($task, 'photo') || str_contains($task, 'video') || str_contains($task, 'media') => 'Media Capture',
                str_contains($task, 'register') || str_contains($task, 'website') => 'Registration Systems',
                default => 'Presentation Support',
            };
        }

        return match (true) {
            str_contains($task, 'report') || str_contains($task, 'highlights') => 'Reporting',
            str_contains($task, 'opportunit') || str_contains($task, 'partnership') || str_contains($task, 'training') || str_contains($task, 'thank-you') || str_contains($task, 'thank you') => 'Opportunities & Follow-up',
            default => 'Impact Measurement',
        };
    }
};
