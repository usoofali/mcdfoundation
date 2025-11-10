<?php

namespace Tests\Feature;

use App\Models\Dependent;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use App\Services\DependentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DependentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a user with appropriate role
        $this->role = Role::where('name', 'Super Admin')->first();
        $this->user = User::factory()->create(['role_id' => $this->role->id]);
    }

    public function test_can_view_dependents_management_page(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);

        $this->actingAs($this->user);

        $response = $this->get(route('dependents.manage', $member));

        $response->assertStatus(200);
        $response->assertSee('Dependents');
    }

    public function test_dependent_model_works(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);

        $dependent = Dependent::factory()->create([
            'member_id' => $member->id,
            'name' => 'Test Child',
            'relationship' => 'child',
            'date_of_birth' => now()->subYears(10),
        ]);

        $this->assertEquals('Test Child', $dependent->name);
        $this->assertEquals('child', $dependent->relationship);
        $this->assertEquals('Child', $dependent->relationship_label);
        $this->assertEquals(10, $dependent->age);
    }

    public function test_can_create_dependent(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);

        $this->actingAs($this->user);

        Volt::test('dependents.manage', ['member' => $member])
            ->call('showCreateModal')
            ->set('form.name', 'John Doe')
            ->set('form.date_of_birth', '2010-01-01')
            ->set('form.relationship', 'child')
            ->set('form.notes', 'Test dependent')
            ->call('save');

        $this->assertDatabaseHas('dependents', [
            'member_id' => $member->id,
            'name' => 'John Doe',
            'relationship' => 'child',
            'notes' => 'Test dependent',
        ]);
    }

    public function test_can_edit_dependent(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $dependent = Dependent::factory()->create([
            'member_id' => $member->id,
            'name' => 'Original Name',
            'relationship' => 'child',
        ]);

        $this->actingAs($this->user);

        Volt::test('dependents.manage', ['member' => $member])
            ->call('showEditModal', $dependent)
            ->set('form.name', 'Updated Name')
            ->set('form.notes', 'Updated notes')
            ->call('save');

        $dependent->refresh();
        $this->assertEquals('Updated Name', $dependent->name);
        $this->assertEquals('Updated notes', $dependent->notes);
    }

    public function test_can_delete_dependent(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $dependent = Dependent::factory()->create([
            'member_id' => $member->id,
            'name' => 'To Be Deleted',
        ]);

        $this->actingAs($this->user);

        Volt::test('dependents.manage', ['member' => $member])
            ->call('delete', $dependent);

        $this->assertSoftDeleted('dependents', ['id' => $dependent->id]);
    }

    public function test_dependent_eligibility_calculation(): void
    {
        $member = Member::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'active',
            'is_complete' => true,
            'registration_date' => now()->subDays(90), // 90 days ago
            'eligibility_start_date' => now()->subDays(30), // Set eligibility date
        ]);

        // Create a child dependent (should be eligible)
        $child = Dependent::factory()->create([
            'member_id' => $member->id,
            'name' => 'Child',
            'relationship' => 'child',
            'date_of_birth' => now()->subYears(10), // 10 years old
        ]);

        // Create a spouse dependent (eligible if member is eligible)
        $spouse = Dependent::factory()->create([
            'member_id' => $member->id,
            'name' => 'Spouse',
            'relationship' => 'spouse',
            'date_of_birth' => now()->subYears(30),
        ]);

        // Debug eligibility status
        $member->refresh();
        $this->assertTrue($child->eligible, 'Child should be eligible (age <= 15)');
        $this->assertTrue($spouse->eligible, 'Spouse should be eligible when member is eligible. Member eligibility: '.($member->is_eligible_for_health ? 'true' : 'false'));
    }

    public function test_dependent_service_works(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $service = app(DependentService::class);

        // Test creating dependent
        $dependentData = [
            'name' => 'Test Dependent',
            'date_of_birth' => '2010-01-01',
            'relationship' => 'child',
            'notes' => 'Test notes',
        ];

        $dependent = $service->createDependent($member, $dependentData);

        $this->assertInstanceOf(Dependent::class, $dependent);
        $this->assertEquals('Test Dependent', $dependent->name);
        $this->assertEquals($member->id, $dependent->member_id);

        // Test getting dependents for member
        $dependents = $service->getDependentsForMember($member);
        $this->assertCount(1, $dependents);

        // Test dependent stats
        $stats = $service->getDependentStats($member);
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['children']);
        $this->assertEquals(0, $stats['spouses']);
    }

    public function test_dependent_authorization_works(): void
    {
        // Create a user with limited permissions
        $limitedRole = Role::where('name', 'Health Officer')->first();
        $limitedUser = User::factory()->create(['role_id' => $limitedRole->id]);

        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $dependent = Dependent::factory()->create(['member_id' => $member->id]);

        $this->actingAs($limitedUser);

        // Health Officer should be able to view dependents
        $response = $this->get(route('dependents.manage', $member));
        $response->assertStatus(200);
    }

    public function test_dependent_relationship_scopes(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);

        Dependent::factory()->create([
            'member_id' => $member->id,
            'relationship' => 'child',
            'name' => 'Child 1',
        ]);

        Dependent::factory()->create([
            'member_id' => $member->id,
            'relationship' => 'spouse',
            'name' => 'Spouse 1',
        ]);

        Dependent::factory()->create([
            'member_id' => $member->id,
            'relationship' => 'parent',
            'name' => 'Parent 1',
        ]);

        $this->assertCount(1, Dependent::children()->get());
        $this->assertCount(1, Dependent::spouses()->get());
        $this->assertCount(1, Dependent::byRelationship('parent')->get());
    }

    public function test_dependent_document_upload(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);
        $service = app(DependentService::class);

        // Create a fake file
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100);

        $dependentData = [
            'name' => 'Test Dependent',
            'date_of_birth' => '2010-01-01',
            'relationship' => 'child',
            'document' => $fakeFile,
        ];

        $dependent = $service->createDependent($member, $dependentData);

        $this->assertNotNull($dependent->document_path);
        $this->assertStringContainsString('dependent-documents', $dependent->document_path);
    }

    public function test_dependent_validation(): void
    {
        $member = Member::factory()->create(['created_by' => $this->user->id]);

        $this->actingAs($this->user);

        // Test validation errors
        Volt::test('dependents.manage', ['member' => $member])
            ->call('showCreateModal')
            ->set('form.name', '') // Empty name
            ->set('form.date_of_birth', 'invalid-date') // Invalid date
            ->set('form.relationship', 'invalid-relationship') // Invalid relationship
            ->call('save')
            ->assertHasErrors(['form.name', 'form.date_of_birth', 'form.relationship']);
    }
}
