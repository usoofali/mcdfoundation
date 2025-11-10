<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_members_index_page(): void
    {
        // Create a user
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('members.index'));

        $response->assertStatus(200);
        $response->assertSee('Members');
    }

    public function test_can_view_member_create_page(): void
    {
        // Create a user
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('members.create'));

        $response->assertStatus(200);
        $response->assertSee('Register Member');
    }
}
