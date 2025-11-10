<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed roles, permissions, etc.
    }

    public function test_can_load_dashboard_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('role', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recent_activities', $data);
        $this->assertArrayHasKey('pending_approvals', $data);
        $this->assertArrayHasKey('quick_actions', $data);
        $this->assertArrayHasKey('charts', $data);
    }

    public function test_dashboard_shows_member_stats(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
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

        // Create some loans
        $member->loans()->create([
            'loan_type' => 'cash',
            'amount' => 50000,
            'repayment_mode' => 'installments',
            'installment_amount' => 5000,
            'repayment_period' => 10,
            'start_date' => now()->subMonths(2),
            'status' => 'disbursed',
            'approved_by' => $user->id,
        ]);

        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertEquals('member', $data['role']);
        $this->assertNotEmpty($data['stats']);
    }

    public function test_dashboard_shows_system_stats_for_admin(): void
    {
        $role = \App\Models\Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        // Create some test data
        Member::factory()->count(5)->create(['created_by' => $user->id]);
        Contribution::factory()->count(3)->create();
        Loan::factory()->count(2)->create();

        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data['stats']);
        $this->assertGreaterThan(0, count($data['stats']));
    }

    public function test_dashboard_includes_recent_activities(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        // Create an audit log entry
        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'App\\Models\\Member',
            'entity_id' => $member->id,
            'before_data' => null,
            'after_data' => $member->toArray(),
        ]);

        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $data['recent_activities']);
        $this->assertGreaterThan(0, $data['recent_activities']->count());
    }

    public function test_dashboard_includes_pending_approvals(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['created_by' => $user->id]);

        // Create a pending loan
        Loan::factory()->create([
            'member_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $data['pending_approvals']);
    }

    public function test_dashboard_includes_quick_actions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data['quick_actions']);
        $this->assertGreaterThan(0, count($data['quick_actions']));

        // Check that quick actions have required fields
        foreach ($data['quick_actions'] as $action) {
            $this->assertArrayHasKey('title', $action);
            $this->assertArrayHasKey('url', $action);
            $this->assertArrayHasKey('icon', $action);
            $this->assertArrayHasKey('color', $action);
        }
    }

    public function test_dashboard_can_access_dashboard_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_shows_user_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back, John Doe');
    }

    public function test_dashboard_handles_empty_data_gracefully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        // Should not throw exceptions even with no data
        $this->assertIsArray($data);
        $this->assertArrayHasKey('role', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertIsArray($data['stats']);
    }
}
