<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_renders_with_circular_logo_and_caption(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('logo-circle.png')
            ->assertSee('Aksesories &amp; Voucher', false)
            ->assertSee('auth.css');
    }
}
