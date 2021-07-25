<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_profile_screen_can_be_rendered()
    {
        if (! Features::enabled(Features::updateProfileInformation())) {
            return $this->markTestSkipped('Profile Information feature is not enabled.');
        }
        $this->actingAs($user = User::factory()->create());
        $response = $this->get('/user/profile');

        $response->assertStatus(200);
    }

    public function test_users_profile_screen_can_not_be_rendered_without_authentication()
    {
        if (! Features::enabled(Features::updateProfileInformation())) {
            return $this->markTestSkipped('Profile Information feature is not enabled.');
        }
        $response = $this->get('/user/profile');

        $response->assertStatus(302);
        $this->assertGuest();
    }
}
