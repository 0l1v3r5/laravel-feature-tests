<?php

namespace Tests\Feature;

use App\Events\PasswordUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated()
    {
        $this->actingAs($user = User::factory()->create());

        Event::fake([
            PasswordUpdated::class,
        ]);

        Livewire::test(UpdatePasswordForm::class)
                ->set('state', [
                    'current_password' => 'p@ssw0rD12345',
                    'password' => 'new@Password12',
                    'password_confirmation' => 'new@Password12',
                ])
                ->call('updatePassword');

        $this->assertTrue(Hash::check('new@Password12', $user->fresh()->password));
        Event::assertDispatched(PasswordUpdated::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_new_password_must_be_different_from_current_password_one()
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
                ->set('state', [
                    'current_password' => 'p@ssw0rD12345',
                    'password' => 'p@ssw0rD12345',
                    'password_confirmation' => 'p@ssw0rD12345',
                ])
                ->call('updatePassword')
                ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('p@ssw0rD12345', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct()
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
                ->set('state', [
                  'current_password' => 'wrong-password',
                  'password' => 'new@passwoRd321',
                  'password_confirmation' => 'new@passwoRd321',
                ])
                ->call('updatePassword')
                ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('p@ssw0rD12345', $user->fresh()->password));
    }

    public function test_new_passwords_must_match()
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
                ->set('state', [
                    'current_password' => 'p@ssw0rD12345',
                    'password' => 'new-passworD@321',
                    'password_confirmation' => 'wrong@Password321',
                ])
                ->call('updatePassword')
                ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('p@ssw0rD12345', $user->fresh()->password));
    }
}
