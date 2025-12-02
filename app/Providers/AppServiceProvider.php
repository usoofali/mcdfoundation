<?php

namespace App\Providers;

use App\Models\Contribution;
use App\Models\CashoutRequest;
use App\Models\Dependent;
use App\Models\HealthClaim;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Policies\CashoutPolicy;
use App\Policies\ContributionPolicy;
use App\Policies\DependentPolicy;
use App\Policies\HealthClaimPolicy;
use App\Policies\LoanPolicy;
use App\Policies\MemberPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Member::class => MemberPolicy::class,
        Dependent::class => DependentPolicy::class,
        Contribution::class => ContributionPolicy::class,
        Loan::class => LoanPolicy::class,
        HealthClaim::class => HealthClaimPolicy::class,
        Program::class => ProgramPolicy::class,
        CashoutRequest::class => CashoutPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
