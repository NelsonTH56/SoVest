<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HomeRouteTest extends DuskTestCase
{
    public function testHomePageExpectedContent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('SoVest')
                    //->assertSee('Social Investment Platform')
                    ->assertPresent('input[name="tryEmail"]')
                    ->assertPresent('input[name="tryPass"]')
                    ->assertSee('Sign Up Here!');
        });
    }
}