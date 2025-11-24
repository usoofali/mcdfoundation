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
            'quick_actions' => $this->getMemberQuickActions($user),
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
     *
     * @param  array  $filters  Filter options:
     *                          - 'level': Approval level (1 = LG, 2 = State, 3 = Project)
     *                          - 'state_id': Filter by state ID
     *                          - 'lga_id': Filter by LGA ID
     */
    protected function getPendingApprovals(array $filters = []): Collection
    {
        $query = Loan::where('status', 'pending')->with(['member']);

        // Filter by approval level
        // Level 1: LG Coordinator (filters by LGA)
        // Level 2: State Coordinator (filters by State)
        // Level 3: Project Coordinator (shows all)
        if (isset($filters['level'])) {
            $level = $filters['level'];
            if ($level == 1 && isset($filters['lga_id'])) {
                // LG level - only show loans from members in this LGA
                $query->whereHas('member', function ($q) use ($filters) {
                    $q->where('lga_id', $filters['lga_id']);
                });
            } elseif ($level == 2 && isset($filters['state_id'])) {
                // State level - show loans from members in this state (but not filtered to specific LGA)
                $query->whereHas('member', function ($q) use ($filters) {
                    $q->where('state_id', $filters['state_id']);
                });
            }
            // Level 3 (Project Coordinator) shows all pending loans, no additional filtering needed
        }

        if (isset($filters['state_id']) && ! isset($filters['level'])) {
            $query->whereHas('member', function ($q) use ($filters) {
                $q->where('state_id', $filters['state_id']);
            });
        }

        if (isset($filters['lga_id']) && ! isset($filters['level'])) {
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
            ['title' => 'Manage Users', 'url' => route('admin.users.index'), 'icon' => 'users', 'color' => 'gray'],
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
            ['title' => 'Approve Claims', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Check Eligibility', 'url' => route('members.index'), 'icon' => 'heart', 'color' => 'red'],
            ['title' => 'View Claims', 'url' => route('contributions.index'), 'icon' => 'document-text', 'color' => 'blue'],
        ];
    }

    protected function getTreasurerQuickActions(): array
    {
        return [
            ['title' => 'Verify Contributions', 'url' => route('contributions.verify'), 'icon' => 'check-circle', 'color' => 'green'],
            ['title' => 'Process Payments', 'url' => route('contributions.verify'), 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'View Fund Ledger', 'url' => route('reports.index'), 'icon' => 'wallet', 'color' => 'purple'],
            ['title' => 'Disburse Loans', 'url' => route('loans.index'), 'icon' => 'banknotes', 'color' => 'blue'],
        ];
    }

    protected function getMemberQuickActions(User $user): array
    {
        $profileUrl = $user->member
            ? route('members.show', $user->member)
            : route('profile.edit');

        return [
            ['title' => 'Submit Contribution', 'url' => route('contributions.submit'), 'icon' => 'currency-dollar', 'color' => 'green'],
            ['title' => 'Apply for Loan', 'url' => route('loans.create'), 'icon' => 'banknotes', 'color' => 'blue'],
            ['title' => 'Submit Health Claim', 'url' => route('contributions.submit'), 'icon' => 'heart', 'color' => 'red'],
            ['title' => 'View My Profile', 'url' => $profileUrl, 'icon' => 'user', 'color' => 'gray'],
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

    /**
     * Format trend value with proper handling of edge cases.
     */
    protected function formatTrend(float $thisPeriod, float $lastPeriod): ?string
    {
        if ($lastPeriod == 0) {
            return $thisPeriod > 0 ? 'New' : null;
        }

        $change = (($thisPeriod - $lastPeriod) / $lastPeriod) * 100;
        $formattedChange = number_format($change, 1);

        if ($change > 0) {
            return "+{$formattedChange}%";
        } elseif ($change < 0) {
            return "{$formattedChange}%";
        }

        return '0%';
    }

    /**
     * Get member growth trend.
     */
    protected function getMemberTrend(): ?string
    {
        $thisMonth = Member::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastMonth = Member::whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get active member trend.
     */
    protected function getActiveMemberTrend(): ?string
    {
        $thisMonth = Member::where('status', 'active')
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->count();

        $lastMonth = Member::where('status', 'active')
            ->whereYear('updated_at', now()->subMonth()->year)
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->count();

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get contribution trend.
     */
    protected function getContributionTrend(): ?string
    {
        $thisMonth = Contribution::where('status', 'paid')
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');

        $lastMonth = Contribution::where('status', 'paid')
            ->whereYear('payment_date', now()->subMonth()->year)
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->sum('amount');

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get loan trend.
     */
    protected function getLoanTrend(): ?string
    {
        $thisMonth = Loan::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $lastMonth = Loan::whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('amount');

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get health claim trend.
     */
    protected function getClaimTrend(): ?string
    {
        $thisMonth = HealthClaim::whereYear('claim_date', now()->year)
            ->whereMonth('claim_date', now()->month)
            ->sum('covered_amount');

        $lastMonth = HealthClaim::whereYear('claim_date', now()->subMonth()->year)
            ->whereMonth('claim_date', now()->subMonth()->month)
            ->sum('covered_amount');

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get fund trend.
     */
    protected function getFundTrend(): ?string
    {
        $thisMonth = FundLedger::whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month)
            ->where('type', 'inflow')
            ->sum('amount');

        $lastMonth = FundLedger::whereYear('transaction_date', now()->subMonth()->year)
            ->whereMonth('transaction_date', now()->subMonth()->month)
            ->where('type', 'inflow')
            ->sum('amount');

        return $this->formatTrend($thisMonth, $lastMonth);
    }

    /**
     * Get contribution trend data for the last 6 months.
     */
    protected function getContributionTrendData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) {
            return Contribution::where('status', 'paid')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount');
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get loan distribution data by status.
     */
    protected function getLoanDistributionData(): array
    {
        $statuses = ['pending', 'approved', 'disbursed', 'repaid', 'defaulted'];

        $labels = array_map('ucfirst', $statuses);

        $data = array_map(function ($status) {
            return Loan::where('status', $status)->count();
        }, $statuses);

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get member growth data for the last 6 months.
     */
    protected function getMemberGrowthData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) {
            return Member::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get loan approval trend data for the last 4 weeks.
     */
    protected function getApprovalTrendData(): array
    {
        $weeks = collect(range(3, 0))->map(function ($weeksAgo) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = $start->copy()->endOfWeek();

            return ['start' => $start, 'end' => $end, 'label' => 'Week '.($weeksAgo + 1)];
        });

        $labels = $weeks->pluck('label')->toArray();

        $data = $weeks->map(function ($week) {
            return Loan::where('status', 'approved')
                ->whereBetween('approval_date', [$week['start'], $week['end']])
                ->count();
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get loan status distribution data.
     */
    protected function getLoanStatusData(): array
    {
        $statuses = ['pending', 'approved', 'disbursed', 'repaid', 'defaulted'];

        $labels = array_map('ucfirst', $statuses);

        $data = array_map(function ($status) {
            return Loan::where('status', $status)->count();
        }, $statuses);

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get state member distribution data by status.
     */
    protected function getStateMemberData(?int $stateId): array
    {
        $baseQuery = Member::query();
        if ($stateId) {
            $baseQuery->where('state_id', $stateId);
        }

        $labels = ['Active', 'Inactive', 'Pending'];

        $data = [
            (clone $baseQuery)->where('status', 'active')->count(),
            (clone $baseQuery)->where('status', 'inactive')->count(),
            (clone $baseQuery)->where('status', 'pending')->count(),
        ];

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get state contribution data for the last 6 months.
     */
    protected function getStateContributionData(?int $stateId): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) use ($stateId) {
            $query = Contribution::where('status', 'paid')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month);

            if ($stateId) {
                $query->whereHas('member', function ($q) use ($stateId) {
                    $q->where('state_id', $stateId);
                });
            }

            return $query->sum('amount');
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get LGA member distribution data by status.
     */
    protected function getLgaMemberData(?int $lgaId): array
    {
        $baseQuery = Member::query();
        if ($lgaId) {
            $baseQuery->where('lga_id', $lgaId);
        }

        $labels = ['Active', 'Inactive', 'Pending'];

        $data = [
            (clone $baseQuery)->where('status', 'active')->count(),
            (clone $baseQuery)->where('status', 'inactive')->count(),
            (clone $baseQuery)->where('status', 'pending')->count(),
        ];

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get LGA contribution data for the last 6 months.
     */
    protected function getLgaContributionData(?int $lgaId): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) use ($lgaId) {
            $query = Contribution::where('status', 'paid')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month);

            if ($lgaId) {
                $query->whereHas('member', function ($q) use ($lgaId) {
                    $q->where('lga_id', $lgaId);
                });
            }

            return $query->sum('amount');
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get health claim data by type.
     */
    protected function getClaimTypeData(): array
    {
        $claimTypes = ['outpatient', 'inpatient', 'surgery', 'maternity'];

        $labels = array_map('ucfirst', $claimTypes);

        $data = array_map(function ($type) {
            return HealthClaim::where('claim_type', $type)->count();
        }, $claimTypes);

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get health claim trend data for the last 6 months.
     */
    protected function getClaimTrendData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) {
            return HealthClaim::whereYear('claim_date', $date->year)
                ->whereMonth('claim_date', $date->month)
                ->sum('covered_amount');
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get fund flow data (inflows vs outflows).
     */
    protected function getFundFlowData(): array
    {
        $labels = ['Inflows', 'Outflows'];

        $inflows = FundLedger::where('type', 'inflow')->sum('amount');
        $outflows = FundLedger::where('type', 'outflow')->sum('amount');

        return [
            'labels' => $labels,
            'data' => [$inflows, $outflows],
        ];
    }

    /**
     * Get monthly transaction data for the last 6 months.
     */
    protected function getMonthlyTransactionData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $labels = $months->map(fn ($date) => $date->format('M Y'))->toArray();

        $data = $months->map(function ($date) {
            return FundLedger::whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
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
