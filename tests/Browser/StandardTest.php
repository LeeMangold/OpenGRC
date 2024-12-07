<?php

namespace Tests\Browser;

use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StandardTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_perform_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/app/login')
                ->assertSee('Sign in')
                ->assertSee('Email address')
                ->assertSee('Password')
                ->type('#data\.email', 'admin@example.com')
                ->type('#data\.password', 'password')
                ->click('button[type="submit"]')
                ->pause(500)
                ->assertSee('Welcome to OpenGRC');
        });
    }

    /** @test */
    public function test_list_standards(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->pause(1000)
                ->clickLink('Standards')
                ->pause(1000)
                ->assertSee('New standard')
                ->pause(1000);

        });
    }

//    /** @test */
//    public function test_create_standard(): void
//    {
//        $this->browse(function (Browser $browser) {
//            $faker = Factory::create();
//            $faker_paragraph = $faker->paragraph;
//            $browser
//                ->clickLink('New standard')
//                ->pause(500)
//                ->assertSee('Create Standard')
//                ->type('#data\.name', 'Automated Test Standard')
//                ->type('#data\.code', 'DUSK-1')
//                ->type('#data\.authority', 'LVM')
//                ->click('div.choices__inner')
//                ->click('div[data-value="In Scope"]')
//                ->type('#data\.reference_url', 'https://www.example.com')
//                ->type('#data\.description', $faker_paragraph)
//                ->pause(2000)
//                ->click('#key-bindings-1')
//                ->pause(500)
//                ->assertSee('View Standard')
//                ->assertSee('Automated Test Standard')
//                ->assertSee('DUSK-1')
//                ->assertSee('LVM')
//                ->assertSee('In Scope')
//                ->assertSee($faker_paragraph);
//        });
//    }
//
//    /** @test */
//    public function test_create_multiple_standards(): void
//    {
//        $this->browse(function (Browser $browser) {
//            $faker = Factory::create();
//            $faker_paragraph = $faker->paragraph;
//            $browser
//                ->visit(route('filament.app.resources.standards.index'))
//                ->pause(500)
//                ->clickLink('New standard')
//                ->pause(500)
//                //Create the first standard
//                ->assertSee('Create Standard')
//                ->type('#data\.name', 'Automated Test Standard - 2')
//                ->type('#data\.code', 'DUSK-2')
//                ->type('#data\.authority', 'LVM')
//                ->click('div.choices__inner')
//                ->click('div[data-value="In Scope"]')
//                ->type('#data\.reference_url', 'https://www.example.com')
//                ->type('#data\.description', $faker_paragraph)
//                ->pause(2000)
//                ->click('#key-bindings-2')
//                ->pause(500)
//                //Create the second standard
//                ->assertSee('Create Standard')
//                ->type('#data\.name', 'Automated Test Standard - 3')
//                ->type('#data\.code', 'DUSK-3')
//                ->type('#data\.authority', 'LVM')
//                ->click('div.choices__inner')
//                ->click('div[data-value="In Scope"]')
//                ->type('#data\.reference_url', 'https://www.example.com')
//                ->type('#data\.description', $faker_paragraph)
//                ->pause(2000)
//                ->click('#key-bindings-1')
//                ->pause(500)
//                //Verify the second standard
//                ->assertSee('View Standard')
//                ->assertSee('Automated Test Standard - 3')
//                ->assertSee('DUSK-3')
//                ->assertSee('LVM')
//                ->assertSee('In Scope')
//                ->assertSee($faker_paragraph);
//        });
//    }
}
