<?php

namespace App\Providers;

use App\Domains\Beneficiaries\Repositories\BeneficiaryRepository;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Policies\BeneficiaryPolicy;
use App\Domains\Assets\Repositories\AssetCategoryRepository;
use App\Domains\Assets\Repositories\AssetCategoryRepositoryInterface;
use App\Domains\Assets\Repositories\AssetRepository;
use App\Domains\Assets\Repositories\AssetRepositoryInterface;
use App\Domains\Projects\Repositories\ProjectRepository;
use App\Domains\Projects\Repositories\ProjectRepositoryInterface;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Policies\AttendanceRegisterPolicy;
use App\Domains\Projects\Policies\ProjectPolicy;
use App\Domains\Projects\Policies\ProjectMilestoneAssessmentPolicy;
use App\Domains\Projects\Repositories\ProjectLocationRepository;
use App\Domains\Projects\Repositories\ProjectLocationRepositoryInterface;
use App\Domains\Projects\Repositories\ProjectEnrollmentRepository;
use App\Domains\Projects\Repositories\ProjectEnrollmentRepositoryInterface;
use App\Domains\BusinessDevelopment\Repositories\BdsApplicationRepository;
use App\Domains\BusinessDevelopment\Repositories\BdsApplicationRepositoryInterface;
use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Policies\BdsApplicationPolicy;
use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Domains\BusinessDevelopment\Policies\BdsPitchSessionPolicy;
use App\Domains\BusinessDevelopment\Repositories\BdsIncubateeRepository;
use App\Domains\BusinessDevelopment\Repositories\BdsIncubateeRepositoryInterface;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Policies\AdjudicationAssessmentPolicy;
use App\Domains\BusinessDevelopment\Adjudication\Repositories\AdjudicationAssessmentRepositoryInterface;
use App\Domains\BusinessDevelopment\Adjudication\Repositories\EloquentAdjudicationAssessmentRepository;
use App\Domains\Facilitators\Repositories\FacilitatorRepository;
use App\Domains\Facilitators\Repositories\FacilitatorRepositoryInterface;
use App\Domains\Programs\Repositories\ProgramRepository;
use App\Domains\Programs\Repositories\ProgramRepositoryInterface;
use App\Domains\Staff\Repositories\StaffDepartmentRepository;
use App\Domains\Staff\Repositories\StaffDepartmentRepositoryInterface;
use App\Domains\Staff\Repositories\StaffRepository;
use App\Domains\Staff\Repositories\StaffRepositoryInterface;
use App\Domains\Stakeholders\Repositories\StakeholderRepository;
use App\Domains\Stakeholders\Repositories\StakeholderRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            BeneficiaryRepositoryInterface::class,
            BeneficiaryRepository::class
        );

        $this->app->bind(
            StakeholderRepositoryInterface::class,
            StakeholderRepository::class
        );

        $this->app->bind(
            FacilitatorRepositoryInterface::class,
            FacilitatorRepository::class
        );

        $this->app->bind(
            ProgramRepositoryInterface::class,
            ProgramRepository::class
        );

        $this->app->bind(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );

        $this->app->bind(
            AssetCategoryRepositoryInterface::class,
            AssetCategoryRepository::class
        );

        $this->app->bind(
            ProjectRepositoryInterface::class,
            ProjectRepository::class
        );

        $this->app->bind(
            ProjectLocationRepositoryInterface::class,
            ProjectLocationRepository::class
        );

        $this->app->bind(
            ProjectEnrollmentRepositoryInterface::class,
            ProjectEnrollmentRepository::class
        );

        $this->app->bind(
            StaffRepositoryInterface::class,
            StaffRepository::class
        );

        $this->app->bind(
            StaffDepartmentRepositoryInterface::class,
            StaffDepartmentRepository::class
        );

        $this->app->bind(
            BdsApplicationRepositoryInterface::class,
            BdsApplicationRepository::class
        );

        $this->app->bind(
            BdsIncubateeRepositoryInterface::class,
            BdsIncubateeRepository::class
        );

        $this->app->bind(
            AdjudicationAssessmentRepositoryInterface::class,
            EloquentAdjudicationAssessmentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::policy(Beneficiary::class, BeneficiaryPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(BdsApplication::class, BdsApplicationPolicy::class);
        Gate::policy(BdsPitchSession::class, BdsPitchSessionPolicy::class);
        Gate::policy(AttendanceRegister::class, AttendanceRegisterPolicy::class);
        Gate::policy(ProjectMilestoneAssessment::class, ProjectMilestoneAssessmentPolicy::class);
        Gate::policy(AdjudicationAssessment::class, AdjudicationAssessmentPolicy::class);

        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'super admin'])
                ? true
                : null;
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
