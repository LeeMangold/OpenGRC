<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginUserTest extends DuskTestCase
{
    /** @test */
    public function test_get_welcome_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Login');
        });
    }

    public function test_perform_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/app/login')
                ->assertSee('Sign in')
                ->assertSee('Email address')
                ->assertSee('Password')
                ->type('#data\.email', 'admin@example.com')
                ->type('#data\.password', '123123123123')
                ->click('button[type="submit"]')
                ->pause(500)
                ->assertSee('Welcome to OpenGRC');
        });
    }

    public function test_user_profile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->click('button[aria-label="User menu"]')
                ->pause(500)
                ->click('[href*="/app/me"]')
                ->pause(500)
                ->assertSee('My Profile')
                ->pause(500);

        });
    }

    public function test_perform_logout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->click('button[aria-label="User menu"]')
                ->pause(500)
                ->click('button[type="submit"]')
                ->pause(500)
                ->assertSee('Sign in');
        });
    }

}
