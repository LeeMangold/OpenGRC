<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to reset rate limiting for login attempts
        Artisan::call('cache:clear');
    }

    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/app/login')
                ->assertSee('Sign in')
                ->assertPresent('#form\\.email')
                ->assertPresent('#form\\.password');
        });
    }

    public function test_user_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/app/login')
                ->type('#form\\.email', 'admin@example.com')
                ->type('#form\\.password', 'password')
                ->press('Sign in')
                ->waitForRoute('filament.app.pages.dashboard', [], 10)
                ->assertRouteIs('filament.app.pages.dashboard')
                ->assertSee('Dashboard');
        });
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/app/login')
                ->type('#form\\.email', 'invalid@example.com')
                ->type('#form\\.password', 'wrongpassword')
                ->press('Sign in')
                ->pause(1000)
                ->assertPathIs('/app/login')
                ->assertSee('These credentials do not match our records');
        });
    }
}
