<?php

use App\Domains\Marketing\Enums\MarketingDeliverableStatus;
use App\Domains\Marketing\Enums\MarketingDeliverableType;
use App\Domains\Marketing\Enums\MarketingOperationalUnit;
use App\Domains\Marketing\Enums\MarketingRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_requests')) {
            Schema::create('marketing_requests', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('objective', 500)->nullable();
                $table->text('description')->nullable();
                $table->text('target_audience')->nullable();
                $table->text('campaign_goal')->nullable();
                $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->foreignId('owner_department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->date('due_date')->nullable();
                $table->string('status')->default(MarketingRequestStatus::Draft->value);
                $table->foreignId('source_marketing_job_id')->nullable()->constrained('marketing_jobs')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'priority'], 'mkt_requests_status_priority_idx');
                $table->index(['owner_department_id', 'status'], 'mkt_requests_dept_status_idx');
            });
        }

        if (! Schema::hasTable('marketing_work_packages')) {
            Schema::create('marketing_work_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('request_id')->constrained('marketing_requests')->cascadeOnDelete();
                $table->string('assigned_unit');
                $table->foreignId('operational_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('workload_status')->default(MarketingRequestStatus::Draft->value);
                $table->date('planned_start_date')->nullable();
                $table->date('planned_end_date')->nullable();
                $table->timestamp('actual_end_date')->nullable();
                $table->timestamps();

                $table->index(['assigned_unit', 'workload_status'], 'mkt_work_pkg_unit_status_idx');
            });
        }

        if (! Schema::hasTable('marketing_deliverables')) {
            Schema::create('marketing_deliverables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('request_id')->constrained('marketing_requests')->cascadeOnDelete();
                $table->foreignId('work_package_id')->constrained('marketing_work_packages')->cascadeOnDelete();
                $table->string('title');
                $table->string('deliverable_type');
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('assigned_unit');
                $table->string('status')->default(MarketingDeliverableStatus::Queued->value);
                $table->date('due_date')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('current_version_id')->nullable();
                $table->foreignId('source_marketing_job_id')->nullable()->constrained('marketing_jobs')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'assigned_unit'], 'mkt_deliverables_status_unit_idx');
                $table->index(['assigned_to_user_id', 'status'], 'mkt_deliverables_assignee_status_idx');
            });
        }

        if (! Schema::hasTable('marketing_deliverable_versions')) {
            Schema::create('marketing_deliverable_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deliverable_id')->constrained('marketing_deliverables')->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('change_notes')->nullable();
                $table->string('asset_disk')->nullable();
                $table->string('asset_path')->nullable();
                $table->string('asset_file_name')->nullable();
                $table->string('asset_mime_type')->nullable();
                $table->unsignedBigInteger('asset_file_size')->nullable();
                $table->string('external_reference', 2048)->nullable();
                $table->string('approval_status')->default('pending');
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['deliverable_id', 'version_number'], 'mkt_dlv_versions_unique');
            });
        }

        if (Schema::hasTable('marketing_deliverables')
            && Schema::hasTable('marketing_deliverable_versions')
            && ! $this->foreignKeyExists('marketing_deliverables', 'mkt_dlv_current_version_fk')) {
            Schema::table('marketing_deliverables', function (Blueprint $table) {
                $table->foreign('current_version_id', 'mkt_dlv_current_version_fk')
                    ->references('id')
                    ->on('marketing_deliverable_versions')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('marketing_assets')) {
            Schema::create('marketing_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deliverable_id')->constrained('marketing_deliverables')->cascadeOnDelete();
                $table->foreignId('deliverable_version_id')->constrained('marketing_deliverable_versions')->cascadeOnDelete();
                $table->string('asset_type');
                $table->string('asset_disk')->nullable();
                $table->string('asset_path')->nullable();
                $table->string('asset_file_name')->nullable();
                $table->string('asset_mime_type')->nullable();
                $table->unsignedBigInteger('asset_file_size')->nullable();
                $table->boolean('reusable')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['asset_type', 'reusable'], 'mkt_assets_type_reusable_idx');
            });
        }

        if (! Schema::hasTable('marketing_publication_records')) {
            Schema::create('marketing_publication_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_asset_id')->constrained('marketing_assets')->cascadeOnDelete();
                $table->string('publication_channel');
                $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->string('external_reference', 2048)->nullable();
                $table->text('publication_notes')->nullable();
                $table->timestamps();

                $table->index(['publication_channel', 'published_at'], 'mkt_publications_channel_date_idx');
            });
        }

        if (! Schema::hasTable('marketing_metric_snapshots')) {
            Schema::create('marketing_metric_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('publication_record_id')->constrained('marketing_publication_records')->cascadeOnDelete();
                $table->date('metric_date');
                $table->unsignedBigInteger('impressions')->nullable();
                $table->unsignedBigInteger('reach')->nullable();
                $table->unsignedBigInteger('engagements')->nullable();
                $table->unsignedBigInteger('clicks')->nullable();
                $table->unsignedBigInteger('sessions')->nullable();
                $table->unsignedBigInteger('conversions')->nullable();
                $table->unsignedBigInteger('followers')->nullable();
                $table->timestamps();

                $table->unique(['publication_record_id', 'metric_date'], 'marketing_metric_unique');
            });
        }

        if (! Schema::hasTable('marketing_activities')) {
            Schema::create('marketing_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('request_id')->constrained('marketing_requests')->cascadeOnDelete();
                $table->nullableMorphs('subject');
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->text('summary');
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['request_id', 'created_at'], 'mkt_activities_request_date_idx');
            });
        }

        if (! Schema::hasTable('marketing_request_comments')) {
            Schema::create('marketing_request_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_request_id')->constrained('marketing_requests')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketing_request_documents')) {
            Schema::create('marketing_request_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_request_id')->constrained('marketing_requests')->cascadeOnDelete();
                $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('document_kind')->default('supporting');
                $table->text('notes')->nullable();
                $table->string('disk');
                $table->string('path');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamps();
            });
        }

        $this->migrateLegacyMarketingJobs();
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_request_documents');
        Schema::dropIfExists('marketing_request_comments');
        Schema::dropIfExists('marketing_activities');
        Schema::dropIfExists('marketing_metric_snapshots');
        Schema::dropIfExists('marketing_publication_records');
        Schema::dropIfExists('marketing_assets');

        if (Schema::hasTable('marketing_deliverables') && $this->foreignKeyExists('marketing_deliverables', 'mkt_dlv_current_version_fk')) {
            Schema::table('marketing_deliverables', function (Blueprint $table) {
                $table->dropForeign('mkt_dlv_current_version_fk');
            });
        }

        Schema::dropIfExists('marketing_deliverable_versions');
        Schema::dropIfExists('marketing_deliverables');
        Schema::dropIfExists('marketing_work_packages');
        Schema::dropIfExists('marketing_requests');
    }

    protected function migrateLegacyMarketingJobs(): void
    {
        if (! Schema::hasTable('marketing_jobs') || ! Schema::hasTable('marketing_requests')) {
            return;
        }

        $jobs = DB::table('marketing_jobs')->orderBy('id')->get();

        foreach ($jobs as $job) {
            $existingRequestId = DB::table('marketing_requests')
                ->where('source_marketing_job_id', $job->id)
                ->value('id');

            if ($existingRequestId) {
                continue;
            }

            $requestId = DB::table('marketing_requests')->insertGetId([
                'title' => $job->title,
                'objective' => $job->title,
                'description' => $job->brief,
                'target_audience' => null,
                'campaign_goal' => $job->brief,
                'requester_user_id' => $job->creator_user_id,
                'approver_user_id' => $job->reviewed_by_user_id,
                'project_id' => null,
                'program_id' => null,
                'event_id' => $job->event_id,
                'owner_department_id' => $job->assigned_department_id ?: $job->creator_department_id,
                'priority' => $job->priority,
                'due_date' => $job->due_date,
                'status' => $this->mapLegacyRequestStatus((string) $job->status),
                'source_marketing_job_id' => $job->id,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
            ]);

            $workPackageId = DB::table('marketing_work_packages')->insertGetId([
                'request_id' => $requestId,
                'assigned_unit' => $this->mapLegacyUnit((string) $job->job_type),
                'operational_owner_user_id' => $job->assigned_to_user_id,
                'workload_status' => $this->mapLegacyRequestStatus((string) $job->status),
                'planned_start_date' => $job->created_at ? Carbon::parse($job->created_at)->toDateString() : null,
                'planned_end_date' => $job->due_date,
                'actual_end_date' => $job->closed_at,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
            ]);

            $deliverableId = DB::table('marketing_deliverables')->insertGetId([
                'request_id' => $requestId,
                'work_package_id' => $workPackageId,
                'title' => $job->title,
                'deliverable_type' => $this->mapLegacyDeliverableType((string) $job->job_type),
                'assigned_to_user_id' => $job->assigned_to_user_id,
                'assigned_unit' => $this->mapLegacyUnit((string) $job->job_type),
                'status' => $this->mapLegacyDeliverableStatus((string) $job->status),
                'due_date' => $job->due_date,
                'review_notes' => $job->approval_notes ?: $job->delivery_notes,
                'approved_at' => $job->approved_at,
                'published_at' => null,
                'source_marketing_job_id' => $job->id,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
            ]);

            $currentVersionId = null;
            if ($job->proof_path || $job->proof_url) {
                $currentVersionId = DB::table('marketing_deliverable_versions')->insertGetId([
                    'deliverable_id' => $deliverableId,
                    'version_number' => 1,
                    'uploaded_by_user_id' => $job->submitted_by_user_id ?: $job->assigned_to_user_id,
                    'change_notes' => $job->delivery_notes,
                    'asset_disk' => $job->proof_disk,
                    'asset_path' => $job->proof_path,
                    'asset_file_name' => $job->proof_file_name,
                    'asset_mime_type' => $job->proof_mime_type,
                    'asset_file_size' => $job->proof_file_size,
                    'external_reference' => $job->proof_url,
                    'approval_status' => $job->approved_at ? 'approved' : (($job->status === 'changes_requested') ? 'changes_requested' : 'pending'),
                    'approved_by_user_id' => $job->reviewed_by_user_id,
                    'approved_at' => $job->approved_at,
                    'created_at' => $job->submitted_for_approval_at ?: $job->created_at,
                    'updated_at' => $job->updated_at,
                ]);

                DB::table('marketing_deliverables')
                    ->where('id', $deliverableId)
                    ->update(['current_version_id' => $currentVersionId]);
            }

            if ($currentVersionId && $job->approved_at) {
                DB::table('marketing_assets')->insert([
                    'deliverable_id' => $deliverableId,
                    'deliverable_version_id' => $currentVersionId,
                    'asset_type' => $this->mapLegacyDeliverableType((string) $job->job_type),
                    'asset_disk' => $job->proof_disk,
                    'asset_path' => $job->proof_path,
                    'asset_file_name' => $job->proof_file_name,
                    'asset_mime_type' => $job->proof_mime_type,
                    'asset_file_size' => $job->proof_file_size,
                    'reusable' => true,
                    'archived_at' => null,
                    'created_at' => $job->approved_at,
                    'updated_at' => $job->updated_at,
                ]);
            }

            if (Schema::hasTable('marketing_job_histories')) {
                $histories = DB::table('marketing_job_histories')
                    ->where('marketing_job_id', $job->id)
                    ->orderBy('id')
                    ->get();

                foreach ($histories as $history) {
                    DB::table('marketing_activities')->insert([
                        'request_id' => $requestId,
                        'subject_type' => 'marketing_deliverable',
                        'subject_id' => $deliverableId,
                        'actor_user_id' => $history->actor_user_id,
                        'action' => $history->action,
                        'summary' => $history->summary,
                        'meta' => $history->meta,
                        'created_at' => $history->created_at,
                        'updated_at' => $history->updated_at,
                    ]);
                }
            }
        }
    }

    protected function mapLegacyRequestStatus(string $status): string
    {
        return match ($status) {
            'open' => MarketingRequestStatus::Submitted->value,
            'in_progress', 'blocked', 'changes_requested' => MarketingRequestStatus::InProduction->value,
            'pending_approval' => MarketingRequestStatus::InReview->value,
            'approved' => MarketingRequestStatus::Completed->value,
            'cancelled' => MarketingRequestStatus::Cancelled->value,
            default => MarketingRequestStatus::Draft->value,
        };
    }

    protected function mapLegacyDeliverableStatus(string $status): string
    {
        return match ($status) {
            'open' => MarketingDeliverableStatus::Queued->value,
            'in_progress', 'blocked' => MarketingDeliverableStatus::InProgress->value,
            'pending_approval' => MarketingDeliverableStatus::InternalReview->value,
            'changes_requested' => MarketingDeliverableStatus::ChangesRequested->value,
            'approved' => MarketingDeliverableStatus::Approved->value,
            'cancelled' => MarketingDeliverableStatus::Archived->value,
            default => MarketingDeliverableStatus::Queued->value,
        };
    }

    protected function mapLegacyDeliverableType(string $type): string
    {
        return match ($type) {
            'graphic_design' => MarketingDeliverableType::Poster->value,
            'social_media' => MarketingDeliverableType::SocialMedia->value,
            'content_plan' => MarketingDeliverableType::ConceptDocument->value,
            'letter_communication' => MarketingDeliverableType::PressRelease->value,
            'email_signature' => MarketingDeliverableType::EmailSignature->value,
            default => MarketingDeliverableType::Other->value,
        };
    }

    protected function mapLegacyUnit(string $type): string
    {
        return match ($type) {
            'graphic_design', 'email_signature' => MarketingOperationalUnit::Graphics->value,
            'social_media' => MarketingOperationalUnit::Digital->value,
            'letter_communication' => MarketingOperationalUnit::Communications->value,
            default => MarketingOperationalUnit::Content->value,
        };
    }

    protected function foreignKeyExists(string $table, string $constraint): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
