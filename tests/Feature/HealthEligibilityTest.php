<?php

namespace Tests\Feature;

use App\Models\HealthcareProvider;
use App\Models\HealthClaim;
use App\Models\Member;
use App\Models\User;
use App\Services\HealthEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_check_member_health_eligibility(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member with registration date 90 days ago
        $member = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
            'registration_date' => now()->subDays(90)->format('Y-m-d'),
        ]);

        // Create some contributions
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

        // Test outpatient eligibility (should be eligible)
        $outpatientEligibility = $member->checkHealthEligibility('outpatient');
        $this->assertTrue($outpatientEligibility['eligible']);

        // Test inpatient eligibility (should not be eligible - needs 5 contributions)
        $inpatientEligibility = $member->checkHealthEligibility('inpatient');
        $this->assertFalse($inpatientEligibility['eligible']);
        $this->assertContains('Member must have at least 5 months of contributions', $inpatientEligibility['issues']);
    }

    public function test_health_claim_model_relationships_work(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member
        $member = Member::factory()->create(['created_by' => $user->id]);

        // Create a healthcare provider
        $provider = HealthcareProvider::factory()->create();

        // Create a health claim
        $claim = HealthClaim::factory()->create([
            'member_id' => $member->id,
            'healthcare_provider_id' => $provider->id,
            'approved_by' => $user->id,
        ]);

        // Test relationships
        $this->assertEquals($member->id, $claim->member->id);
        $this->assertEquals($provider->id, $claim->healthcareProvider->id);
        $this->assertEquals($user->id, $claim->approver->id);
        $this->assertTrue($member->healthClaims->contains($claim));
    }

    public function test_health_claim_coverage_calculation(): void
    {
        $claim = new HealthClaim([
            'billed_amount' => 10000,
            'coverage_percent' => 90.00,
        ]);

        $this->assertEquals(9000, $claim->calculateCoverageAmount());
        $this->assertEquals(1000, $claim->calculateCopayAmount());
    }

    public function test_health_claim_status_labels(): void
    {
        $claim = new HealthClaim(['status' => 'submitted']);
        $this->assertEquals('Submitted', $claim->status_label);

        $claim = new HealthClaim(['status' => 'approved']);
        $this->assertEquals('Approved', $claim->status_label);

        $claim = new HealthClaim(['status' => 'paid']);
        $this->assertEquals('Paid', $claim->status_label);
    }

    public function test_health_claim_type_labels(): void
    {
        $claim = new HealthClaim(['claim_type' => 'outpatient']);
        $this->assertEquals('Outpatient', $claim->claim_type_label);

        $claim = new HealthClaim(['claim_type' => 'inpatient']);
        $this->assertEquals('Inpatient', $claim->claim_type_label);

        $claim = new HealthClaim(['claim_type' => 'surgery']);
        $this->assertEquals('Surgery', $claim->claim_type_label);

        $claim = new HealthClaim(['claim_type' => 'maternity']);
        $this->assertEquals('Maternity', $claim->claim_type_label);
    }

    public function test_health_eligibility_service_stats(): void
    {
        $service = app(HealthEligibilityService::class);

        // Create some members
        $user = User::factory()->create();
        $member1 = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
            'registration_date' => now()->subDays(90)->format('Y-m-d'),
        ]);

        $member2 = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
            'registration_date' => now()->subDays(30)->format('Y-m-d'), // Not eligible yet
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

        $stats = $service->getEligibilityStats();

        $this->assertEquals(2, $stats['total_active_members']);
        $this->assertEquals(1, $stats['outpatient_eligible']);
        $this->assertEquals(0, $stats['inpatient_eligible']);
    }
}
