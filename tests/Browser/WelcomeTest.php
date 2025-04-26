<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WelcomeTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function testLoadWelcomePage(): void
    {        
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('OpenGRC')
                    ->assertSee('Login');                
        });        
    }

    public function testClickLoginButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->clickLink('Login')
                    ->assertPathIs('/app/login');
        });
    }

    
    
}
