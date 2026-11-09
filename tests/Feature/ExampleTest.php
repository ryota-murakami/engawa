<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_sends_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_root_sends_members_to_social_home(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $this->actingAs($user)->get('/')->assertRedirect('/home');
    }
}
