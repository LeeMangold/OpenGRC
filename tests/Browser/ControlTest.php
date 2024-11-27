<?php

namespace Tests\Browser;

use App\Models\Standard;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ControlTest extends DuskTestCase
{
    /** @test */
    public function test_list_control(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(route('filament.app.resources.controls.index'))
                ->pause(500)
                ->loginAs(1)
                ->visit(route('filament.app.resources.controls.index'))
                ->pause(500)
                ->assertSee('New control');
        });
    }

    /** @test */
    public function test_create_control(): void
    {
        $this->browse(function (Browser $browser) {
            $faker = Factory::create();

            //Create a standard to test with
            $standard = new Standard();
            $standard->name = 'Automated Test Standard C1';
            $standard->code = 'DUSK-C1';
            $standard->authority = 'LVM';
            $standard->status = 'In Scope';
            $standard->reference_url = 'https://www.example.com';
            $standard->description = $faker->paragraph;
            $standard->save();

            $faker_paragraph = $faker->paragraph;
            $faker_paragraph2 = $faker->paragraph;
            $browser
                ->clickLink('New control')
                ->pause(500)
                ->assertSee('Create Control')
                ->type('#data\.title', 'This is an automated test control')
                ->type('#data\.code', 'DUSK-C1')
                ->type('#data\.description', $faker_paragraph)
                ->type('#data\.discussion', $faker_paragraph2)

                ->click('div.choices__inner')
                ->click('div[data-value="Automated Test Standard C1"]')

                ->pause(2000)
                ->click('#key-bindings-1')
                ->pause(500)
                ->assertSee('View Control');
//                ->assertSee('Automated Test Standard')
//                ->assertSee('DUSK-1')
//                ->assertSee('LVM')
//                ->assertSee('In Scope')
//                ->assertSee($faker_paragraph);
        });
    }
}
