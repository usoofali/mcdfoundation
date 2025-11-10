<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Contribution;
use App\Models\FundLedger;
use App\Models\HealthClaim;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * Get dashboard data based on user role.
     */
    public function getDashboardData(User $user): array
    {
        $role = $user->role?->name ?? 'member';

        return match ($role) {
            'Super Admin' => $this->getSuperAdminDashboard(),
            'Project Coordinator' => $this->getProjectCoordinatorDashboard(),
            'State Coordinator' => $this->getStateCoordinatorDashboard($user),
            'LG Coordinator' => $this->getLgCoordinatorDashboard($user),
            'Health Officer' => $this->getHealthOfficerDashboard(),
            'Treasurer' => $this->getTreasurerDashboard(),
            default => $this->getMemberDashboard($user),
        };
    }

    /**
     * Super Admin Dashboard - All system statistics.
     */
    protected function getSuperAdminDashboard(): array
    {
        return [
            'role' => 'Super Admin',
            'title' => 'System Overview',
            'stats' => $this->getSystemStats(),
            'recent_activities' => $this->getRecentActivities(20),
            'pending_approvals' => $this->getPendingApprovals(),
            'quick_actions' => $this->getSuperAdminQuickActions(),
            'charts' => $this->getSystemCharts(),
        ];
    }

    /**
     * Project Coordinator Dashboard - High-level oversight.
     */
    protected function getProjectCoordinatorDashboard(): array
    {
        return [
            'role' => 'project_coordinator',
            'title' => 'Project Coordinator Dashboard',
            'stats' => $this->getProjectStats(),
            'recent_activities' => $this->getRecentActivities(15),
            'pending_approvals' => $this->getPendingApprovals(['level' => 3]),
            'quick_actions' => $this->getProjectCoordinatorQuickActions(),
            'charts' => $this->getProjectCharts(),
        ];
    }

    /**
     * State Coordinator Dashboard - State-level management.
     */
    protected function getStateCoordinatorDashboard(User $user): array
    {
        $stateId = $user->state_id;

        return [
            'role' => 'state_coordinator',
            'title' => 'State Coordinator Dashboard',
            'stats' => $this->getStateStats($stateId),
            'recent_activities' => $this->getRecentActivities(15, ['state_id' => $stateId]),
            'pending_approvals' => $this->getPendingApprovals(['level' => 2, 'state_id' => $stateId]),
            'quick_actions' => $this->getStateCoordinatorQuickActions(),
            'charts' => $this->getStateCharts($stateId),
        ];
    }

    /**
     * LG Coordinator Dashboard - Local government level.
     */
    protected function getLgCoordinatorDashboard(User $user): array
    {
        $lgaId = $user->lga_id;

        return [
            'role' => 'lg_coordinator',
            'title' => 'LG Coordinator Dashboard',
            'stats' => $this->getLgaStats($lgaId),
            'recent_activities' => $this->getRecentActivities(15, ['lga_id' => $lgaId]),
            'pending_approvals' => $this->getPendingApprovals(['level' => 1, 'lga_id' => $lgaId]),
            'quick_actions' => $this->getLgCoordinatorQuickActions(),
            'charts' => $this->getLgaCharts($lgaId),
        ];
    }

    /**
     * Health Officer Dashboard - Health-related statistics.
     */
    protected function getHealthOfficerDashboard(): array
    {
        return [
            'role' => 'health_officer',
            'title' => 'Health Officer Dashboard',
            'stats' => $this->getHealthStats(),
            'recent_activities' => $this->getRecentActivities(15, ['type' => 'health']),
            'pending_approvals' => $this->getPendingHealthClaims(),
            'quick_actions' => $this->getHealthOfficerQuickActions(),
            'charts' => $this->getHealthCharts(),
        ];
    }

    /**
     * Treasurer Dashboard - Financial overview.
     */
    protected function getTreasurerDashboard(): array
    {
        return [
            'role' => 'treasurer',
            'title' => 'Treasurer Dashboard',
            'stats' => $this->getFinancialStats(),
            'recent_activities' => $this->getRecentActivities(15, ['type' => 'financial']),
            'pending_approvals' => $this->getPendingPayments(),
            'quick_actions' => $this->getTreasurerQuickActions(),
            'charts' => $this->getFinancialCharts(),
        ];
    }

    /**
     * Member Dashboard - Personal member view.
     */
    protected function getMemberDashboard(User $user): array
    {
        $member = $user->member;

        return [
            'role' => 'member',
            'title' => 'My Dashboard',
            'stats' => $this->getMemberStats($member),
            'recent_activities' => $this->getMemberRecentActivities($member),
            'pending_approvals' => new Collection,
            'quick_actions' => $this->getMemberQuickActions(),
            'charts' => $this->getMemberCharts($member),
        ];
    }

    /**
     * Get system-wide statistics.
     */
    protected function getSystemStats(): array
    {
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();
        $totalContributions = Contribution::where('status', 'paid')->sum('amount');
        $pendingContributions = Contribution::where('status', 'pending')->count();
        $pendingVerifications = Contribution::pendingVerification()->count();
        $totalLoans = Loan::sum('amount');
        $outstandingLoans = Loan::whereIn('status', ['disbursed', 'defaulted'])->get()->sum('outstanding_balance');
        $totalClaims = HealthClaim::sum('covered_amount');
        $fundBalance = FundLedger::getCurrentBalance();

        return [
            [
                'title' => 'Total Members',
                'value' => number_format($totalMembers),
                'icon' => 'users',
                'color' => 'blue',
                'trend' => $this->getMemberTrend(),
            ],
            [
                'title' => 'Active Members',
                'value' => number_format($activeMembers),
                'icon' => 'user-plus',
                'color' => 'green',
                'trend' => $this->getActiveMemberTrend(),
            ],
            [
                'title' => 'Total Contributions',
                'value' => '₦'.number_format($totalContributions, 2),
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => $this->getContributionTrend(),
            ],
            [
                'title' => 'Pending Verifications',
                'value' => number_format($pendingVerifications),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Outstanding Loans',
                'value' => '₦'.number_format($outstandingLoans, 2),
                'icon' => 'banknotes',
                'color' => 'yellow',
                'trend' => $this->getLoanTrend(),
            ],
            [
                'title' => 'Health Claims Paid',
                'value' => '₦'.number_format($totalClaims, 2),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => $this->getClaimTrend(),
            ],
            [
                'title' => 'Fund Balance',
                'value' => '₦'.number_format($fundBalance, 2),
                'icon' => 'wallet',
                'color' => 'purple',
                'trend' => $this->getFundTrend(),
            ],
        ];
    }

    /**
     * Get project-level statistics.
     */
    protected function getProjectStats(): array
    {
        $pendingLoans = Loan::where('status', 'pending')->count();
        $approvedLoans = Loan::where('status', 'approved')->count();
        $disbursedLoans = Loan::where('status', 'disbursed')->count();
        $pendingClaims = HealthClaim::where('status', 'submitted')->count();
        $approvedClaims = HealthClaim::where('status', 'approved')->count();

        return [
            [
                'title' => 'Pending Loan Approvals',
                'value' => number_format($pendingLoans),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Approved Loans',
                'value' => number_format($approvedLoans),
                'icon' => 'check-circle',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Disbursed Loans',
                'value' => number_format($disbursedLoans),
                'icon' => 'banknotes',
                'color' => 'blue',
                'trend' => null,
            ],
            [
                'title' => 'Pending Health Claims',
                'value' => number_format($pendingClaims),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => null,
            ],
            [
                'title' => 'Approved Claims',
                'value' => number_format($approvedClaims),
                'icon' => 'check-circle',
                'color' => 'green',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get state-level statistics.
     */
    protected function getStateStats(?int $stateId): array
    {
        $query = Member::query();
        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        $totalMembers = $query->count();
        $activeMembers = $query->where('status', 'active')->count();
        $eligibleMembers = $query->get()->filter(fn ($m) => $m->checkHealthEligibility('outpatient')['eligible'])->count();

        return [
            [
                'title' => 'State Members',
                'value' => number_format($totalMembers),
                'icon' => 'users',
                'color' => 'blue',
                'trend' => null,
            ],
            [
                'title' => 'Active Members',
                'value' => number_format($activeMembers),
                'icon' => 'user-plus',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Eligible Members',
                'value' => number_format($eligibleMembers),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get LGA-level statistics.
     */
    protected function getLgaStats(?int $lgaId): array
    {
        $query = Member::query();
        if ($lgaId) {
            $query->where('lga_id', $lgaId);
        }

        $totalMembers = $query->count();
        $activeMembers = $query->where('status', 'active')->count();
        $contributions = Contribution::whereHas('member', function ($q) use ($lgaId) {
            if ($lgaId) {
                $q->where('lga_id', $lgaId);
            }
        })->where('status', 'paid')->sum('amount');

        return [
            [
                'title' => 'LGA Members',
                'value' => number_format($totalMembers),
                'icon' => 'users',
                'color' => 'blue',
                'trend' => null,
            ],
            [
                'title' => 'Active Members',
                'value' => number_format($activeMembers),
                'icon' => 'user-plus',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Contributions Collected',
                'value' => '₦'.number_format($contributions, 2),
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get health-related statistics.
     */
    protected function getHealthStats(): array
    {
        $pendingClaims = HealthClaim::where('status', 'submitted')->count();
        $approvedClaims = HealthClaim::where('status', 'approved')->count();
        $paidClaims = HealthClaim::where('status', 'paid')->count();
        $totalCovered = HealthClaim::where('status', 'paid')->sum('covered_amount');
        $eligibleMembers = Member::where('status', 'active')->get()->filter(fn ($m) => $m->checkHealthEligibility('outpatient')['eligible'])->count();

        return [
            [
                'title' => 'Pending Claims',
                'value' => number_format($pendingClaims),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Approved Claims',
                'value' => number_format($approvedClaims),
                'icon' => 'check-circle',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Paid Claims',
                'value' => number_format($paidClaims),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => null,
            ],
            [
                'title' => 'Total Covered Amount',
                'value' => '₦'.number_format($totalCovered, 2),
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Eligible Members',
                'value' => number_format($eligibleMembers),
                'icon' => 'users',
                'color' => 'blue',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get financial statistics.
     */
    protected function getFinancialStats(): array
    {
        $fundBalance = FundLedger::getCurrentBalance();
        $monthlyInflows = FundLedger::where('type', 'inflow')
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');
        $monthlyOutflows = FundLedger::where('type', 'outflow')
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');
        $pendingPayments = HealthClaim::where('status', 'approved')->count();
        $loanDisbursements = Loan::where('status', 'disbursed')->sum('amount');

        return [
            [
                'title' => 'Fund Balance',
                'value' => '₦'.number_format($fundBalance, 2),
                'icon' => 'wallet',
                'color' => 'purple',
                'trend' => null,
            ],
            [
                'title' => 'Monthly Inflows',
                'value' => '₦'.number_format($monthlyInflows, 2),
                'icon' => 'arrow-trending-up',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Monthly Outflows',
                'value' => '₦'.number_format($monthlyOutflows, 2),
                'icon' => 'arrow-trending-down',
                'color' => 'red',
                'trend' => null,
            ],
            [
                'title' => 'Pending Payments',
                'value' => number_format($pendingPayments),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Total Disbursed',
                'value' => '₦'.number_format($loanDisbursements, 2),
                'icon' => 'banknotes',
                'color' => 'blue',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get member-specific statistics.
     */
    protected function getMemberStats(?Member $member): array
    {
        if (! $member) {
            return [];
        }

        $contributions = $member->contributions()->where('status', 'paid')->sum('amount');
        $pendingContributions = $member->contributions()->where('status', 'pending')->count();
        $loans = $member->loans()->sum('amount');
        $outstandingBalance = $member->loans()->whereIn('status', ['disbursed', 'defaulted'])->get()->sum('outstanding_balance');
        $claims = $member->healthClaims()->where('status', 'paid')->sum('covered_amount');
        $isEligible = $member->checkHealthEligibility('outpatient')['eligible'];

        return [
            [
                'title' => 'Total Contributions',
                'value' => '₦'.number_format($contributions, 2),
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => null,
            ],
            [
                'title' => 'Pending Contributions',
                'value' => number_format($pendingContributions),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Total Loans',
                'value' => '₦'.number_format($loans, 2),
                'icon' => 'banknotes',
                'color' => 'blue',
                'trend' => null,
            ],
            [
                'title' => 'Outstanding Balance',
                'value' => '₦'.number_format($outstandingBalance, 2),
                'icon' => 'exclamation-triangle',
                'color' => 'yellow',
                'trend' => null,
            ],
            [
                'title' => 'Claims Covered',
                'value' => '₦'.number_format($claims, 2),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => null,
            ],
            [
                'title' => 'Health Eligible',
                'value' => $isEligible ? 'Yes' : 'No',
                'icon' => $isEligible ? 'check-circle' : 'x-circle',
                'color' => $isEligible ? 'green' : 'red',
                'trend' => null,
            ],
        ];
    }

    /**
     * Get recent activities.
     */
    protected function getRecentActivities(int $limit = 10, array $filters = []): Collection
    {
        $query = AuditLog::with(['user'])->latest();

        // Apply filters
        if (isset($filters['state_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('state_id', $filters['state_id']);
            });
        }

        if (isset($filters['lga_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('lga_id', $filters['lga_id']);
            });
        }

        if (isset($filters['type'])) {
            $query->where('entity_type', 'like', '%'.$filters['type'].'%');
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get member recent activities.
     */
    protected function getMemberRecentActivities(?Member $member): Collection
    {
        if (! $member) {
            return new Collection;
        }

        return AuditLog::where('entity_type', 'App\\Models\\Member')
            ->where('entity_id', $member->id)
            ->with(['user'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get pending approvals.
     */
    protected function getPendingApprovals(array $filters = []): Collection
    {
        $query = Loan::where('status', 'pending')->with(['member']);

        if (isset($filters['level'])) {
            // This would need to be implemented based on approval levels
        }

        if (isset($filters['state_id'])) {
            $query->whereHas('member', function ($q) use ($filters) {
                $q->where('state_id', $filters['state_id']);
            });
        }

        if (isset($filters['lga_id'])) {
            $query->whereHas('member', function ($q) use ($filters) {
                $q->where('lga_id', $filters['lga_id']);
            });
        }

        return $query->limit(10)->get();
    }

    /**
     * Get pending health claims.
     */
    protected function getPendingHealthClaims(): Collection
    {
        return HealthClaim::where('status', 'submitted')
            ->with(['member', 'healthcareProvider'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get pending payments.
     */
    protected function getPendingPayments(): Collection
    {
        return HealthClaim::where('status', 'approved')
            ->with(['member', 'healthcareProvider'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get quick actions based on role.
     */
    protected function getSuperAdminQuickActions(): array
    {
        return [
            ['title' => 'Add Member', 'url' => route('members.create'), 'icon' => 'user-plus', 'color' => 'blue'],
            ['title' => 'Record Contribution', 'url' => route('contributions.create'), 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'View Reports', 'url' => route('reports.index'), 'icon' => 'chart-bar', 'color' => 'purple'],
            ['title' => 'Manage Users', 'url' => '#', 'icon' => 'users', 'color' => 'gray'],
        ];
    }

    protected function getProjectCoordinatorQuickActions(): array
    {
        return [
            ['title' => 'Verify Contributions', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Approve Loans', 'url' => route('loans.index'), 'icon' => 'check-circle', 'color' => 'blue'],
            ['title' => 'View Members', 'url' => route('members.index'), 'icon' => 'users', 'color' => 'blue'],
            ['title' => 'View Reports', 'url' => route('reports.index'), 'icon' => 'chart-bar', 'color' => 'purple'],
        ];
    }

    protected function getStateCoordinatorQuickActions(): array
    {
        return [
            ['title' => 'Verify Contributions', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Approve Loans', 'url' => route('loans.index'), 'icon' => 'check-circle', 'color' => 'blue'],
            ['title' => 'View State Members', 'url' => route('members.index'), 'icon' => 'users', 'color' => 'blue'],
            ['title' => 'Record Contribution', 'url' => route('contributions.create'), 'icon' => 'currency-dollar', 'color' => 'green'],
        ];
    }

    protected function getLgCoordinatorQuickActions(): array
    {
        return [
            ['title' => 'Verify Contributions', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Approve Loans', 'url' => route('loans.index'), 'icon' => 'check-circle', 'color' => 'blue'],
            ['title' => 'Record Contribution', 'url' => route('contributions.create'), 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'Add Member', 'url' => route('members.create'), 'icon' => 'user-plus', 'color' => 'blue'],
        ];
    }

    protected function getHealthOfficerQuickActions(): array
    {
        return [
            ['title' => 'Approve Claims', 'url' => '#', 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Check Eligibility', 'url' => '#', 'icon' => 'heart', 'color' => 'red'],
            ['title' => 'View Claims', 'url' => '#', 'icon' => 'document-text', 'color' => 'blue'],
        ];
    }

    protected function getTreasurerQuickActions(): array
    {
        return [
            ['title' => 'Verify Contributions', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Process Payments', 'url' => '#', 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'View Fund Ledger', 'url' => '#', 'icon' => 'wallet', 'color' => 'purple'],
            ['title' => 'Disburse Loans', 'url' => route('loans.index'), 'icon' => 'banknotes', 'color' => 'blue'],
        ];
    }

    protected function getMemberQuickActions(): array
    {
        return [
            ['title' => 'Submit Contribution', 'url' => route('contributions.submit'), 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'Apply for Loan', 'url' => route('loans.create'), 'icon' => 'banknotes', 'color' => 'blue'],
            ['title' => 'Submit Health Claim', 'url' => '#', 'icon' => 'heart', 'color' => 'red'],
            ['title' => 'View My Profile', 'url' => '#', 'icon' => 'user', 'color' => 'gray'],
        ];
    }

    /**
     * Get chart data for different roles.
     */
    protected function getSystemCharts(): array
    {
        return [
            'contribution_trend' => $this->getContributionTrendData(),
            'loan_distribution' => $this->getLoanDistributionData(),
            'member_growth' => $this->getMemberGrowthData(),
        ];
    }

    protected function getProjectCharts(): array
    {
        return [
            'approval_trend' => $this->getApprovalTrendData(),
            'loan_status' => $this->getLoanStatusData(),
        ];
    }

    protected function getStateCharts(?int $stateId): array
    {
        return [
            'state_members' => $this->getStateMemberData($stateId),
            'state_contributions' => $this->getStateContributionData($stateId),
        ];
    }

    protected function getLgaCharts(?int $lgaId): array
    {
        return [
            'lga_members' => $this->getLgaMemberData($lgaId),
            'lga_contributions' => $this->getLgaContributionData($lgaId),
        ];
    }

    protected function getHealthCharts(): array
    {
        return [
            'claim_types' => $this->getClaimTypeData(),
            'claim_trend' => $this->getClaimTrendData(),
        ];
    }

    protected function getFinancialCharts(): array
    {
        return [
            'fund_flow' => $this->getFundFlowData(),
            'monthly_transactions' => $this->getMonthlyTransactionData(),
        ];
    }

    protected function getMemberCharts(?Member $member): array
    {
        if (! $member) {
            return [];
        }

        return [
            'contribution_history' => $this->getMemberContributionHistory($member),
            'loan_history' => $this->getMemberLoanHistory($member),
        ];
    }

    /**
     * Helper methods for trend calculations and chart data.
     */
    protected function getMemberTrend(): ?string
    {
        $thisMonth = Member::whereMonth('created_at', now()->month)->count();
        $lastMonth = Member::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    protected function getActiveMemberTrend(): ?string
    {
        $thisMonth = Member::where('status', 'active')->whereMonth('updated_at', now()->month)->count();
        $lastMonth = Member::where('status', 'active')->whereMonth('updated_at', now()->subMonth()->month)->count();

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    protected function getContributionTrend(): ?string
    {
        $thisMonth = Contribution::where('status', 'paid')->whereMonth('payment_date', now()->month)->sum('amount');
        $lastMonth = Contribution::where('status', 'paid')->whereMonth('payment_date', now()->subMonth()->month)->sum('amount');

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    protected function getLoanTrend(): ?string
    {
        $thisMonth = Loan::whereMonth('created_at', now()->month)->sum('amount');
        $lastMonth = Loan::whereMonth('created_at', now()->subMonth()->month)->sum('amount');

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    protected function getClaimTrend(): ?string
    {
        $thisMonth = HealthClaim::whereMonth('claim_date', now()->month)->sum('covered_amount');
        $lastMonth = HealthClaim::whereMonth('claim_date', now()->subMonth()->month)->sum('covered_amount');

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    protected function getFundTrend(): ?string
    {
        $thisMonth = FundLedger::whereMonth('transaction_date', now()->month)->where('type', 'inflow')->sum('amount');
        $lastMonth = FundLedger::whereMonth('transaction_date', now()->subMonth()->month)->where('type', 'inflow')->sum('amount');

        if ($lastMonth == 0) {
            return null;
        }

        $change = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return $change > 0 ? "+{$change}%" : "{$change}%";
    }

    /**
     * Chart data methods (simplified for now).
     */
    protected function getContributionTrendData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [10000, 15000, 12000, 18000, 20000, 22000],
        ];
    }

    protected function getLoanDistributionData(): array
    {
        return [
            'labels' => ['Pending', 'Approved', 'Disbursed', 'Repaid'],
            'data' => [5, 10, 15, 25],
        ];
    }

    protected function getMemberGrowthData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [50, 75, 100, 125, 150, 175],
        ];
    }

    protected function getApprovalTrendData(): array
    {
        return [
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            'data' => [3, 5, 2, 7],
        ];
    }

    protected function getLoanStatusData(): array
    {
        return [
            'labels' => ['Pending', 'Approved', 'Disbursed', 'Repaid'],
            'data' => [8, 12, 20, 35],
        ];
    }

    protected function getStateMemberData(?int $stateId): array
    {
        return [
            'labels' => ['Active', 'Inactive', 'Pending'],
            'data' => [100, 20, 5],
        ];
    }

    protected function getStateContributionData(?int $stateId): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [5000, 7500, 6000, 9000, 10000, 11000],
        ];
    }

    protected function getLgaMemberData(?int $lgaId): array
    {
        return [
            'labels' => ['Active', 'Inactive', 'Pending'],
            'data' => [25, 5, 2],
        ];
    }

    protected function getLgaContributionData(?int $lgaId): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [1000, 1500, 1200, 1800, 2000, 2200],
        ];
    }

    protected function getClaimTypeData(): array
    {
        return [
            'labels' => ['Outpatient', 'Inpatient', 'Surgery', 'Maternity'],
            'data' => [15, 8, 3, 5],
        ];
    }

    protected function getClaimTrendData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [2000, 3000, 2500, 4000, 3500, 4500],
        ];
    }

    protected function getFundFlowData(): array
    {
        return [
            'labels' => ['Inflows', 'Outflows'],
            'data' => [50000, 30000],
        ];
    }

    protected function getMonthlyTransactionData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [10000, 15000, 12000, 18000, 20000, 22000],
        ];
    }

    protected function getMemberContributionHistory(?Member $member): array
    {
        if (! $member) {
            return ['labels' => [], 'data' => []];
        }

        $contributions = $member->contributions()
            ->where('status', 'paid')
            ->orderBy('payment_date')
            ->get();

        return [
            'labels' => $contributions->pluck('payment_date')->map(fn ($date) => $date->format('M Y'))->toArray(),
            'data' => $contributions->pluck('amount')->toArray(),
        ];
    }

    protected function getMemberLoanHistory(?Member $member): array
    {
        if (! $member) {
            return ['labels' => [], 'data' => []];
        }

        $loans = $member->loans()->orderBy('created_at')->get();

        return [
            'labels' => $loans->pluck('created_at')->map(fn ($date) => $date->format('M Y'))->toArray(),
            'data' => $loans->pluck('amount')->toArray(),
        ];
    }
}
