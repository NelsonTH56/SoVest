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
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertSee('Sign Up Here!');
        });
    }
}