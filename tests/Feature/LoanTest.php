<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_loans_index_page(): void
    {
        // Create a user
        $user = User::factory()->create();

        $role = Role::create(['name' => 'Test Role']);
        $permission = Permission::create([
            'name' => 'view_loans',
            'module' => 'loans',
            'description' => 'View loans',
        ]);

        $role->assignPermission($permission);

        $user->role()->associate($role)->save();

        $this->actingAs($user);

        $response = $this->get('/loans');

        $response->assertStatus(200);
    }

    public function test_loan_model_relationships_work(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member
        $member = Member::factory()->create(['created_by' => $user->id]);

        // Create a loan
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'approved_by' => $user->id,
        ]);

        // Test relationships
        $this->assertEquals($member->id, $loan->member->id);
        $this->assertEquals($user->id, $loan->approver->id);
        $this->assertTrue($member->loans->contains($loan));
    }

    public function test_loan_outstanding_balance_calculation(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member
        $member = Member::factory()->create(['created_by' => $user->id]);

        // Create a loan
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'amount' => 10000,
        ]);

        // Create repayments
        LoanRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 3000,
        ]);

        LoanRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 2000,
        ]);

        // Test outstanding balance calculation
        $this->assertEquals(5000, $loan->outstanding_balance);
        $this->assertEquals(5000, $loan->total_repaid);
        $this->assertFalse($loan->is_fully_repaid);
    }

    public function test_loan_eligibility_check(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a member
        $member = Member::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
        ]);

        // Create a loan
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
        ]);

        // Test eligibility check
        $eligibility = $loan->checkEligibility();

        $this->assertIsArray($eligibility);
        $this->assertArrayHasKey('eligible', $eligibility);
        $this->assertArrayHasKey('issues', $eligibility);
    }

    public function test_loan_monthly_installment_calculation(): void
    {
        $loan = new Loan([
            'amount' => 12000,
            'repayment_period' => '12 months',
        ]);

        $this->assertEquals(1000, $loan->calculateMonthlyInstallment());
    }

    public function test_loan_status_labels(): void
    {
        $loan = new Loan(['status' => 'pending']);
        $this->assertEquals('Pending Approval', $loan->status_label);

        $loan = new Loan(['status' => 'approved']);
        $this->assertEquals('Approved', $loan->status_label);

        $loan = new Loan(['status' => 'disbursed']);
        $this->assertEquals('Disbursed', $loan->status_label);
    }
}
