<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNotSame('new-password', $user->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_profile_page_is_authenticated_and_exposes_password_form(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_site_settings_exposes_admin_email_and_password_controls(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)
            ->get(route('site-settings.edit'))
            ->assertOk()
            ->assertSee('Adres e-mail do logowania i resetu hasła')
            ->assertSee('name="email"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password_confirmation"', false);

        $this->actingAs($user)
            ->from(route('site-settings.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'reset@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('site-settings.edit'));

        $this->assertSame('reset@example.com', $user->fresh()->email);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
