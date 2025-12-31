<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contribution;
use App\Models\HealthClaim;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\State;
use App\Models\Lga;
use App\Models\User;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\Dashboard\DynamicDashboardBuilder;
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

    /** @test */
    public function it_can_load_dashboard_data(): void
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

    /** @test */
    public function it_uses_dynamic_builder_when_enabled(): void
    {
        config(['dashboard.features.use_dynamic_builder' => true]);

        $role = Role::where('name', 'Finance Officer')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('stats', $data);
    }

    /** @test */
    public function it_uses_legacy_system_when_dynamic_builder_disabled(): void
    {
        config(['dashboard.features.use_dynamic_builder' => false]);

        $role = Role::where('name', 'Finance Officer')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data);
        $this->assertEquals('finance_officer', $data['role']);
    }

    /** @test */
    public function it_shows_widgets_based_on_permissions(): void
    {
        $role = Role::where('name', 'Finance Officer')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $builder = app(DynamicDashboardBuilder::class);
        $data = $builder->build($user);

        // Finance Officer should see financial widgets
        $this->assertIsArray($data['stats']);
        $this->assertNotEmpty($data['stats']);
    }

    /** @test */
    public function it_filters_data_by_location_for_staff(): void
    {
        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->create(['name' => 'Ikeja', 'state_id' => $state->id]);

        $role = Role::where('name', 'Finance Officer')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
        ]);

        // Create members in different locations
        $memberInLocation = Member::factory()->create([
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'created_by' => $user->id,
        ]);

        $otherState = State::factory()->create(['name' => 'Abuja']);
        $memberOutsideLocation = Member::factory()->create([
            'state_id' => $otherState->id,
            'created_by' => $user->id,
        ]);

        $builder = app(DynamicDashboardBuilder::class);
        $data = $builder->build($user);

        // Should have location info
        $this->assertArrayHasKey('location_info', $data);
        $this->assertEquals('Lagos', $data['location_info']['state']);
        $this->assertEquals('Ikeja', $data['location_info']['lga']);
    }

    /** @test */
    public function it_does_not_filter_for_super_admin(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $builder = app(DynamicDashboardBuilder::class);
        $data = $builder->build($user);

        // Super Admin should not have location info (sees all data)
        $this->assertNull($data['location_info']);
    }

    /** @test */
    public function it_includes_location_info_for_staff(): void
    {
        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->create(['name' => 'Ikeja', 'state_id' => $state->id]);

        $role = Role::where('name', 'Health Officer')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
        ]);

        $builder = app(DynamicDashboardBuilder::class);
        $data = $builder->build($user);

        $this->assertArrayHasKey('location_info', $data);
        $this->assertIsArray($data['location_info']);
        $this->assertEquals('Lagos', $data['location_info']['state']);
        $this->assertEquals('Ikeja', $data['location_info']['lga']);
    }

    /** @test */
    public function it_includes_recent_activities(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        // Create an audit log entry
        AuditLog::create([
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

    /** @test */
    public function it_includes_quick_actions(): void
    {
        $role = Role::where('name', 'Finance Officer')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user);

        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);

        $this->assertIsArray($data['quick_actions']);

        // Check that quick actions have required fields
        foreach ($data['quick_actions'] as $action) {
            $this->assertArrayHasKey('title', $action);
            $this->assertArrayHasKey('url', $action);
            $this->assertArrayHasKey('icon', $action);
            $this->assertArrayHasKey('color', $action);
        }
    }

    /** @test */
    public function it_can_access_dashboard_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function it_shows_user_name_on_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back, John Doe');
    }

    /** @test */
    public function it_handles_empty_data_gracefully(): void
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

    /** @test */
    public function widget_registry_can_register_widgets(): void
    {
        $registry = app(DashboardWidgetRegistry::class);

        $this->assertTrue($registry->has('members', 'stats'));
        $this->assertTrue($registry->has('contributions', 'stats'));
        $this->assertTrue($registry->has('loans', 'stats'));
    }

    /** @test */
    public function widget_registry_filters_by_permissions(): void
    {
        $role = Role::where('name', 'Health Officer')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $registry = app(DashboardWidgetRegistry::class);
        $widgets = $registry->getAvailableWidgets($user, 'stats');

        // Health Officer should see health-related widgets
        $this->assertNotEmpty($widgets);
    }

    /** @test */
    public function it_shows_different_dashboards_for_different_roles(): void
    {
        $financeRole = Role::where('name', 'Finance Officer')->first();
        $healthRole = Role::where('name', 'Health Officer')->first();

        $financeUser = User::factory()->create(['role_id' => $financeRole->id]);
        $healthUser = User::factory()->create(['role_id' => $healthRole->id]);

        $builder = app(DynamicDashboardBuilder::class);

        $financeData = $builder->build($financeUser);
        $healthData = $builder->build($healthUser);

        // Both should have data but potentially different widgets
        $this->assertIsArray($financeData['stats']);
        $this->assertIsArray($healthData['stats']);

        $this->assertEquals('Finance Officer Dashboard', $financeData['title']);
        $this->assertEquals('Health Officer Dashboard', $healthData['title']);
    }
}
