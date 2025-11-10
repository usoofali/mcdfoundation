<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_contributions_index_page(): void
    {
        // Create a user
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('contributions.index'));

        $response->assertStatus(200);
        $response->assertSee('Contributions');
    }

    public function test_can_view_contribution_create_page(): void
    {
        // Create a user
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('contributions.create'));

        $response->assertStatus(200);
        $response->assertSee('Record Contribution');
    }

    public function test_contribution_model_relationships_work(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member
        $member = Member::factory()->create(['created_by' => $user->id]);

        // Create a contribution plan
        $plan = ContributionPlan::factory()->create();

        // Create a contribution
        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $plan->id,
            'collected_by' => $user->id,
        ]);

        // Test relationships
        $this->assertEquals($member->id, $contribution->member->id);
        $this->assertEquals($plan->id, $contribution->contributionPlan->id);
        $this->assertEquals($user->id, $contribution->collector->id);
        $this->assertTrue($member->contributions->contains($contribution));
    }

    public function test_contribution_receipt_number_generation(): void
    {
        $receiptNumber = Contribution::generateReceiptNumber();

        $this->assertStringStartsWith('RCP', $receiptNumber);
        $this->assertStringContainsString(date('Y'), $receiptNumber);
        $this->assertStringContainsString(date('m'), $receiptNumber);
    }

    public function test_contribution_late_fine_calculation(): void
    {
        $contribution = new Contribution([
            'amount' => 1000,
            'payment_date' => now()->addDays(5),
            'period_end' => now(),
        ]);

        $this->assertEquals(500, $contribution->calculateLateFine());
    }

    public function test_contribution_total_amount_calculation(): void
    {
        $contribution = new Contribution([
            'amount' => 1000,
            'fine_amount' => 200,
        ]);

        $this->assertEquals(1200, $contribution->total_amount);
    }
}
