<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        if (! Features::enabled(Features::registration())) {
            return $this->markTestSkipped('Registration feature is not enabled.');
        }

        $response = $this->createUserSendingRequestToPostRegister();
        $user = $response['user'];
        $response = $response['response'];

        $this->assertNotNull($user);
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_users_can_not_register_with_invalid_data()
    {
        if (! Features::enabled(Features::registration())) {
            return $this->markTestSkipped('Registration feature is not enabled.');
        }

        $first_name = '';
        $last_name = '';
        $email = 'jgkjr@gmail.com';
        $password = 'huhj';

        $response = $this->post('/register', [
            'last_name' => $last_name,
            'first_name' => $first_name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $user = User::where('email', $email)->where('last_name', $last_name)->where('first_name', $first_name)->first();
        $this->assertNull($user);
        $response->assertSessionHasErrors(['password', 'last_name', 'first_name']);
    }

    public function test_verification_mail_is_sent_after_new_user_register()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification feature is not enabled.');
        }
        Notification::fake();

        $first_name = 'Userx';
        $last_name = 'Testex';
        $email = 'testxxxxxx@gmail.com';
        $password = 'p@ssw0rD12345';

        $response = $this->post('/register', [
            'last_name' => $last_name,
            'first_name' => $first_name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'password_confirmation' => $password,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);
        $user = User::where('email', $email)->where('last_name', $last_name)->where('first_name', $first_name)->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification, $channel) use ($user) {

            // retrive the mail content
            $mailData = $notification->toMail($user)->toArray();
            // Log::info('mess', [$notification]);
            $response = $this->get($mailData['actionUrl']);
            $response->assertStatus(302);

            $this->assertEquals(Lang::get('Verify Email Address'), $mailData['subject']);
            $this->assertEquals(Lang::get('Verify Email Address'), $mailData['actionText']);

            return true;
        });
    }

    public function test_new_users_can_not_register_with_password_that_does_not_pass_the_password_validation_rules()
    {
        if (! Features::enabled(Features::registration())) {
            return $this->markTestSkipped('Registration feature is not enabled.');
        }

        // Require at least 6 characters...
        // Require at least one numeric character...
        // Require at least one special character...
        $invalidPasswords = ['passw', '@fe41', 'adertg', '1234@', '12345678901234', 'azertyuiopqsd'];

        for ($i = 0; $i < count($invalidPasswords); $i++) {
            $first_name = $this->faker->firstName;
            $last_name = $this->faker->lastName;
            $email = $this->faker->freeEmail; // Generate a free email address to get a real valid email address
            $password = $invalidPasswords[$i];

            $response = $this->post('/register', [
                'last_name' => $last_name,
                'first_name' => $first_name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
                'password_confirmation' => $password,
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            ]);

            $this->assertDatabaseMissing('users', [
              'first_name' => $this->faker->firstName,
              'last_name' => $this->faker->lastName,
              'email' => $this->faker->freeEmail,
            ]);
            $response->assertSessionHasErrors(['password']);
        }
    }

    public function test_new_users_can_not_register_without_checking_terms_and_conditions()
    {
        if (! Features::enabled(Features::registration()) && Jetstream::hasTermsAndPrivacyPolicyFeature()) {
            return $this->markTestSkipped('Terms and Privacies on Registration feature is not enabled.');
        }

        $first_name = 'Userx';
        $last_name = 'Testex';
        $email = 'testxxxxxx@gmail.com';
        $password = 'p@ssw0rD12345';

        $response = $this->post('/register', [
            'last_name' => $last_name,
            'first_name' => $first_name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'password_confirmation' => $password,
            'terms' => 0,
        ]);

        $user = User::where('email', $email)->where('last_name', $last_name)->where('first_name', $first_name)->first();
        $this->assertNull($user);
    }

    public function test_new_users_can_not_register_with_email_that_does_not_pass_the_email_validation_rules()
    {
        if (! Features::enabled(Features::registration())) {
            return $this->markTestSkipped('Registration feature is not enabled.');
        }

        // require dns validation
        // disposable email is not allowed
        $invalidEmails = [
          'test@examplefzjfzjfjkjfz.com', // domain has invalid dns check
          'user@temporarymail.org', // disposable email
          'azertyuiopqsd',
          'azertyui@fr',
        ];

        for ($i = 0; $i < count($invalidEmails); $i++) {
            $first_name = $this->faker->firstName;
            $last_name = $this->faker->lastName;
            $email = $invalidEmails[$i] ;
            $password = 'Password@12345';

            $response = $this->post('/register', [
                'last_name' => $last_name,
                'first_name' => $first_name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
                'password_confirmation' => $password,
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            ]);

            $this->assertDatabaseMissing('users', [
              'first_name' => $this->faker->firstName,
              'last_name' => $this->faker->lastName,
              'email' => $this->faker->freeEmail,
            ]);
            $response->assertSessionHasErrors(['email']);
        }
    }

    public function test_registered_event_is_firing_after_new_user_register()
    {
        $this->markTestIncomplete('Test has not implemented yet');
    }

    // public function test_welcome_notification_is_sent_after_new_user_register()
    // {
    //     if (! Features::enabled(Features::emailVerification())) {
    //         return $this->markTestSkipped('Email verification feature is not enabled.');
    //     }
    //     Notification::fake();

    //     $data = $this->createUserSendingRequestToPostRegister();
    //     $user = $data['user'];
    //     $response = $data['response'];

    //     $this->assertNotNull($user);

    //     Notification::assertSentTo($user, WelcomeUserNotification::class, function ($notification, $channel, $notifiable) use ($user) {

    //         // retrive the mail content
    //         $mailData = $notification->toMail($user)->toArray();
    //         // Log::info('mess', [$notification]);
    //         $response = $this->get($mailData['actionUrl']);
    //         $response->assertStatus(302);

    //         $this->assertEquals(Lang::get('Welcome to: app_name, :username!', [
    //           'user_name' => $user->getFullName(),
    //           'app_name' => config('app.name'),
    //         ]), $mailData['subject']);
    //         $this->assertEquals(Lang::get('Start the adventure'), $mailData['actionText']);

    //         return $user->id === $notifiable->id;
    //     });
    // }

    public function createUserSendingRequestToPostRegister()
    {
        $first_name = 'Userx';
        $last_name = 'Testex';
        $email = 'owoowoowoowox@gmail.com';
        $password = 'p@ssw0rD12345';

        $response = $this->post('/register', [
            'last_name' => $last_name,
            'first_name' => $first_name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'password_confirmation' => $password,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $user = User::where('email', $email)->where('last_name', $last_name)->where('first_name', $first_name)->first();

        return ['user' => $user, 'response' => $response];
    }
}
