<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_enter_the_social_home_from_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/home');
    }
}
