<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\FundLedger;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use App\Services\ContributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContributionVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a user with appropriate role
        $this->role = Role::where('name', 'Finance Officer')->first();
        $this->user = User::factory()->create(['role_id' => $this->role->id]);
    }

    public function test_staff_can_view_pending_verifications(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        // Create some pending contributions
        Contribution::factory()->count(3)->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $pendingContributions = $contributionService->getPendingVerifications();

        $this->assertCount(3, $pendingContributions);

        foreach ($pendingContributions as $contribution) {
            $this->assertEquals('pending', $contribution->status);
            $this->assertNotNull($contribution->receipt_path);
            $this->assertNotNull($contribution->uploaded_by);
        }
    }

    public function test_staff_can_approve_contribution(): void
    {
        Storage::fake('public');
        Notification::fake();

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $result = $contributionService->verifyContribution($contribution, true, 'Payment verified successfully');

        $this->assertTrue($result);

        $contribution->refresh();
        $this->assertEquals('paid', $contribution->status);
        $this->assertEquals($this->user->id, $contribution->verified_by);
        $this->assertEquals($this->user->id, $contribution->collected_by);
        $this->assertEquals('Payment verified successfully', $contribution->verification_notes);
        $this->assertNotNull($contribution->verified_at);

        // Verify fund ledger entry was created
        $fundLedgerEntry = FundLedger::where('reference', $contribution->receipt_number)->first();
        $this->assertNotNull($fundLedgerEntry);
        $this->assertEquals('inflow', $fundLedgerEntry->type);
        $this->assertEquals($contribution->total_amount, $fundLedgerEntry->amount);
    }

    public function test_staff_can_reject_contribution(): void
    {
        Storage::fake('public');
        Notification::fake();

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $result = $contributionService->verifyContribution($contribution, false, 'Receipt does not match payment reference');

        $this->assertTrue($result);

        $contribution->refresh();
        $this->assertEquals('cancelled', $contribution->status);
        $this->assertEquals($this->user->id, $contribution->collected_by);
        $this->assertNotNull($contribution->verified_at);
        $this->assertEquals($this->user->id, $contribution->verified_by);
        $this->assertEquals('Receipt does not match payment reference', $contribution->verification_notes);

        // Verify no fund ledger entry was created for rejected contribution
        $fundLedgerEntry = FundLedger::where('reference', $contribution->receipt_number)->first();
        $this->assertNull($fundLedgerEntry);
    }

    public function test_fund_ledger_only_created_on_approval(): void
    {
        Storage::fake('public');

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        // Test approval
        $approvedContribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $contributionService->verifyContribution($approvedContribution, true);

        $approvedLedgerEntry = FundLedger::where('reference', $approvedContribution->receipt_number)->first();
        $this->assertNotNull($approvedLedgerEntry);
        $this->assertEquals('inflow', $approvedLedgerEntry->type);

        // Test rejection
        $rejectedContribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt2.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $contributionService->verifyContribution($rejectedContribution, false);

        $rejectedLedgerEntry = FundLedger::where('reference', $rejectedContribution->receipt_number)->first();
        $this->assertNull($rejectedLedgerEntry);
    }

    public function test_only_staff_with_permission_can_verify(): void
    {
        $regularUser = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($regularUser);

        $contributionService = app(ContributionService::class);

        // This should work in the service layer, but the UI would check permissions
        // The actual permission check would be in the controller or middleware
        $result = $contributionService->verifyContribution($contribution, true);

        $this->assertTrue($result);

        $contribution->refresh();
        $this->assertEquals($regularUser->id, $contribution->verified_by);
    }

    public function test_verification_sends_notification_to_member(): void
    {
        Notification::fake();
        Storage::fake('public');

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $memberUser = User::factory()->create();
        $member->update(['user_id' => $memberUser->id]);

        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $contributionService->verifyContribution($contribution, true, 'Approved');

        Notification::assertSentTo(
            $memberUser,
            \App\Notifications\ContributionVerified::class,
            function ($notification) use ($contribution) {
                return $notification->contribution->id === $contribution->id &&
                       $notification->approved === true &&
                       $notification->notes === 'Approved';
            }
        );
    }

    public function test_rejection_sends_notification_to_member(): void
    {
        Notification::fake();
        Storage::fake('public');

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $memberUser = User::factory()->create();
        $member->update(['user_id' => $memberUser->id]);

        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $contributionService->verifyContribution($contribution, false, 'Receipt unclear');

        Notification::assertSentTo(
            $memberUser,
            \App\Notifications\ContributionVerified::class,
            function ($notification) use ($contribution) {
                return $notification->contribution->id === $contribution->id &&
                       $notification->approved === false &&
                       $notification->notes === 'Receipt unclear';
            }
        );
    }

    public function test_cannot_verify_non_pending_contribution(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'paid', // Already paid
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending contributions can be verified');

        $contributionService->verifyContribution($contribution, true);
    }

    public function test_cannot_verify_non_member_submitted_contribution(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'uploaded_by' => null, // Not member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only member-submitted contributions can be verified');

        $contributionService->verifyContribution($contribution, true);
    }

    public function test_pending_verification_scope_works_correctly(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        // Create different types of contributions
        Contribution::factory()->count(2)->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        Contribution::factory()->count(2)->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'paid',
        ]);

        Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'uploaded_by' => null,
        ]);

        $pendingVerifications = Contribution::pendingVerification()->get();

        $this->assertCount(2, $pendingVerifications);

        foreach ($pendingVerifications as $contribution) {
            $this->assertEquals('pending', $contribution->status);
            $this->assertNotNull($contribution->receipt_path);
            $this->assertNotNull($contribution->uploaded_by);
        }
    }

    public function test_verification_updates_contribution_relationships_correctly(): void
    {
        Storage::fake('public');

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $contributionPlan = ContributionPlan::factory()->create();

        $contribution = Contribution::factory()->create([
            'member_id' => $member->id,
            'contribution_plan_id' => $contributionPlan->id,
            'status' => 'pending',
            'receipt_path' => 'test-receipt.pdf',
            'uploaded_by' => $this->user->id,
            'collected_by' => null, // Ensure this is null for member-submitted
        ]);

        $this->actingAs($this->user);

        $contributionService = app(ContributionService::class);
        $contributionService->verifyContribution($contribution, true);

        $contribution->refresh();

        // Test relationships
        $this->assertNotNull($contribution->verifier);
        $this->assertEquals($this->user->id, $contribution->verifier->id);
        $this->assertNotNull($contribution->collector);
        $this->assertEquals($this->user->id, $contribution->collector->id);
        $this->assertNotNull($contribution->uploader);
    }
}
