<?php

namespace Tests\Feature\Auth;

use App\Models\Lga;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $state = State::factory()->create();
        $lga = Lga::factory()->create(['state_id' => $state->id]);

        $response = $this->post(route('register.store'), [
            'full_name' => 'John',
            'family_name' => 'Doe',
            'marital_status' => 'single',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'nin' => '12345678901',
            'hometown' => 'Enugu',
            'phone' => '+2348000000000',
            'email' => 'test@example.com',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'address' => '123 Test Street',
            'occupation' => 'Engineer',
            'workplace' => 'Acme Corp',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('members.complete', absolute: false));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('members', [
            'nin' => '12345678901',
            'status' => 'pre_registered',
            'is_complete' => false,
        ]);
    }
}
