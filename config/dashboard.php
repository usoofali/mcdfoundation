<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Register all available dashboard widgets here. Widgets are organized
    | by type (stats, charts, actions) and will be displayed based on
    | user permissions.
    |
    */

    'widgets' => [
        'stats' => [
            'members' => \App\Services\Dashboard\Widgets\Stats\MembersStatsWidget::class,
            'contributions' => \App\Services\Dashboard\Widgets\Stats\ContributionsStatsWidget::class,
            'loans' => \App\Services\Dashboard\Widgets\Stats\LoansStatsWidget::class,
            'health_claims' => \App\Services\Dashboard\Widgets\Stats\HealthClaimsStatsWidget::class,
            'cashouts' => \App\Services\Dashboard\Widgets\Stats\CashoutsStatsWidget::class,
            'overdue_contributions' => \App\Services\Dashboard\Widgets\Stats\OverdueContributionsStatsWidget::class,
            'due_soon_contributions' => \App\Services\Dashboard\Widgets\Stats\DueSoonContributionsStatsWidget::class,
        ],

        'charts' => [
            'contribution_trend' => \App\Services\Dashboard\Widgets\Charts\ContributionTrendWidget::class,
            'loan_distribution' => \App\Services\Dashboard\Widgets\Charts\LoanDistributionWidget::class,
            'member_growth' => \App\Services\Dashboard\Widgets\Charts\MemberGrowthWidget::class,
            'claim_types' => \App\Services\Dashboard\Widgets\Charts\ClaimTypesWidget::class,
        ],

        'actions' => [
            'member_actions' => \App\Services\Dashboard\Widgets\Actions\MemberActionsWidget::class,
            'financial_actions' => \App\Services\Dashboard\Widgets\Actions\FinancialActionsWidget::class,
            'health_actions' => \App\Services\Dashboard\Widgets\Actions\HealthActionsWidget::class,
            'program_actions' => \App\Services\Dashboard\Widgets\Actions\ProgramActionsWidget::class,
            'member_next_payment' => \App\Services\Dashboard\Widgets\Actions\MemberNextPaymentWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific dashboard features.
    |
    */

    'features' => [
        'use_dynamic_builder' => env('DASHBOARD_USE_DYNAMIC_BUILDER', true),
        'cache_widgets' => env('DASHBOARD_CACHE_WIDGETS', false),
        'cache_ttl' => env('DASHBOARD_CACHE_TTL', 300), // 5 minutes
    ],
];
