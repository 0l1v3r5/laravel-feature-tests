<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available()
    {
        if (! Features::enabled(Features::updateProfileInformation())) {
            return $this->markTestSkipped('Profile Information feature is not enabled.');
        }
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->last_name, $component->state['last_name']);
        $this->assertEquals($user->first_name, $component->state['first_name']);
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated()
    {
        if (! Features::enabled(Features::updateProfileInformation())) {
            return $this->markTestSkipped('Profile Information feature is not enabled.');
        }
        $this->actingAs($user = User::factory()->create());

        $response = Livewire::test(UpdateProfileInformationForm::class)
                ->set('state', [
                  'last_name' => 'Test lastName',
                  'first_name' => 'Test firstName',
                  'email' => 'testxxxxxxxxx@gmail.com',
                ])
                ->call('updateProfileInformation');

        $this->assertEquals('Test lastName', $user->fresh()->last_name);
        $this->assertEquals('Test firstName', $user->fresh()->first_name);
        $this->assertEquals('testxxxxxxxxx@gmail.com', $user->fresh()->email);
        $response->assertStatus(200);
    }

    public function test_email_on_profile_information_screen_can_not_be_updated_without_email_validation_rules()
    {
        if (! Features::enabled(Features::updateProfileInformation())) {
            return $this->markTestSkipped('Profile Information feature is not enabled.');
        }
        $this->actingAs($user = User::factory()->create());

        // require dns validation
        // disposable email is not allowed
        $invalidEmails = [
          'test@examplefzjfzjfjkjfz.com', // domain has invalid dns check
          'user@temporarymail.org', // disposable email
          'azertyuiopqsd',
          'azertyui@fr',
        ];

        for ($i = 0; $i < count($invalidEmails); $i++) {
            // code
            $response = Livewire::test(UpdateProfileInformationForm::class)
                  ->set('state', ['last_name' => 'Test lastName', 'first_name' => 'Test firstName', 'email' => $invalidEmails[$i]])
                  ->call('updateProfileInformation');

            $updatedUser = User::where('email', $invalidEmails[$i])->where('last_name', 'Test lastName')->where('first_name', 'Test firstName')->first();
            $this->assertNull($updatedUser);

            $this->assertFalse($invalidEmails[$i] === $user->fresh()->email);
            // $response->assertSessionHasErrorsIn('updateProfileInformation', ['email'], null);
            // $response->assertStatus(200);
        }
    }
}
