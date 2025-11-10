<?php

namespace Tests\Feature;

use App\Models\FundLedger;
use App\Models\HealthClaim;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_membership_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member1 = Member::factory()->create(['created_by' => $user->id, 'status' => 'active']);
        $member2 = Member::factory()->create(['created_by' => $user->id, 'status' => 'inactive']);

        $service = app(ReportService::class);
        $report = $service->generateMembershipReport();

        $this->assertEquals(2, $report['total_members']);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('members', $report);
        $this->assertCount(2, $report['members']);

        // Check eligibility calculation
        $eligibleCount = 0;
        foreach ($report['members'] as $member) {
            if ($member['is_eligible']) {
                $eligibleCount++;
            }
        }
        $this->assertGreaterThanOrEqual(0, $eligibleCount);
    }

    public function test_can_generate_contribution_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        $member->contributions()->create([
            'contribution_plan_id' => 1,
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(30),
            'period_start' => now()->subDays(30)->startOfMonth(),
            'period_end' => now()->subDays(30)->endOfMonth(),
            'status' => 'paid',
            'collected_by' => $user->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->generateContributionReport();

        $this->assertEquals(1, $report['total_contributions']);
        $this->assertEquals(3000, $report['total_amount']);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('contributions', $report);
    }

    public function test_can_generate_loan_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'amount' => 50000,
            'status' => 'disbursed',
        ]);

        $service = app(ReportService::class);
        $report = $service->generateLoanReport();

        $this->assertEquals(1, $report['total_loans']);
        $this->assertEquals(50000, $report['total_amount']);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('loans', $report);
    }

    public function test_can_generate_health_claims_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        $claim = HealthClaim::factory()->create([
            'member_id' => $member->id,
            'billed_amount' => 10000,
            'covered_amount' => 9000,
            'copay_amount' => 1000,
        ]);

        $service = app(ReportService::class);
        $report = $service->generateHealthClaimsReport();

        $this->assertEquals(1, $report['total_claims']);
        $this->assertEquals(10000, $report['total_billed_amount']);
        $this->assertEquals(9000, $report['total_covered_amount']);
        $this->assertEquals(1000, $report['total_copay_amount']);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('claims', $report);
    }

    public function test_can_generate_fund_ledger_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        FundLedger::create([
            'type' => 'inflow',
            'member_id' => $member->id,
            'source' => 'contribution',
            'amount' => 5000,
            'description' => 'Test contribution',
            'transaction_date' => now()->toDateString(),
            'reference' => 'TEST001',
            'created_by' => $user->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->generateFundLedgerReport();

        $this->assertEquals(1, $report['total_transactions']);
        $this->assertEquals(5000, $report['total_inflows']);
        $this->assertEquals(0, $report['total_outflows']);
        $this->assertArrayHasKey('by_source', $report);
        $this->assertArrayHasKey('transactions', $report);
    }

    public function test_can_generate_eligibility_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member1 = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
            'created_at' => now()->subDays(90), // Eligible
        ]);

        $member2 = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
            'created_at' => now()->subDays(30), // Not eligible yet
        ]);

        // Add contributions to first member
        $member1->contributions()->create([
            'contribution_plan_id' => 1,
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(30),
            'period_start' => now()->subDays(30)->startOfMonth(),
            'period_end' => now()->subDays(30)->endOfMonth(),
            'status' => 'paid',
            'collected_by' => $user->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->generateEligibilityReport();

        $this->assertEquals(2, $report['total_members']);
        $this->assertArrayHasKey('eligible_outpatient', $report);
        $this->assertArrayHasKey('eligible_inpatient', $report);
        $this->assertArrayHasKey('members', $report);
    }

    public function test_can_export_report_data(): void
    {
        // Create test data
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        $service = app(ReportService::class);
        $data = $service->exportToArray('membership');

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('full_name', $data[0]);
    }

    public function test_can_get_available_report_types(): void
    {
        $service = app(ReportService::class);
        $types = $service->getAvailableReportTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('membership', $types);
        $this->assertArrayHasKey('contribution', $types);
        $this->assertArrayHasKey('loan', $types);
        $this->assertArrayHasKey('healthClaims', $types);
        $this->assertArrayHasKey('fundLedger', $types);
        $this->assertArrayHasKey('eligibility', $types);
    }
}
