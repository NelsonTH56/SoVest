<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegistrationValidationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test validation for empty fields
     */
    public function testEmptyFieldsValidation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Create Account')
                    ->press('Create Account')
                    ->pause(1000)
                    ->assertPresent('.alert-danger');
        });
    }

    /**
     * Test validation for invalid email
     */
    public function testInvalidEmailValidation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Create Account') 
                    ->type('firstName', 'Test')
                    ->type('lastName', 'User')
                    ->type('newEmail', 'invalid-email')
                    ->type('newPass', 'password123')
                    ->type('confirmPass', 'password123')
                    ->type('newMajor', 'Computer Science')
                    ->type('newYear', 'Senior')
                    ->type('newScholarship', 'Merit')
                    ->check('termsAgreement')
                    ->press('Create Account')
                    ->pause(1000)
                    ->assertSee('Please enter a valid email address')
                    ->assertPresent('.alert-danger');
        });
    }

    /**
     * Test validation for short password
     */
    public function testShortPasswordValidation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Create Account')
                    ->type('firstName', 'Test')
                    ->type('lastName', 'User')
                    ->type('newEmail', 'test@example.com')
                    ->type('newPass', 'short')
                    ->type('confirmPass', 'short')
                    ->type('newMajor', 'Computer Science')
                    ->type('newYear', 'Senior')
                    ->type('newScholarship', 'Merit')
                    ->check('termsAgreement')
                    ->press('Create Account')
                    ->pause(1000)
                    ->assertSee('Password must be at least 6 characters long')
                    ->assertPresent('.alert-danger');
        });
    }

    /**
     * Test validation for terms agreement checkbox
     */
    public function testTermsAgreementValidation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Create Account')
                    ->type('firstName', 'Test')
                    ->type('lastName', 'User')
                    ->type('newEmail', 'test@example.com')
                    ->type('newPass', 'password123')
                    ->type('confirmPass', 'password123')
                    ->type('newMajor', 'Computer Science')
                    ->type('newYear', 'Senior')
                    ->type('newScholarship', 'Merit')
                    // Intentionally not checking termsAgreement
                    ->press('Create Account')
                    ->pause(1000)
                    ->assertSee('Please check your information and try again')
                    ->assertPresent('.alert-danger');
        });
    }

    /**
     * Test successful registration with valid data
     */
    public function testSuccessfulRegistration(): void
    {
        $uniqueEmail = 'test_' . time() . '@example.com';
        
        $this->browse(function (Browser $browser) use ($uniqueEmail) {
            $browser->visit('/register')
                    ->assertSee('Create Account')
                    ->type('firstName', 'Test')
                    ->type('lastName', 'User')
                    ->type('newEmail', $uniqueEmail)
                    ->type('newPass', 'password123')
                    ->type('confirmPass', 'password123')
                    ->type('newMajor', 'Computer Science')
                    ->type('newYear', 'Senior')
                    ->type('newScholarship', 'Merit')
                    ->check('termsAgreement')
                    ->press('Create Account')
                    ->pause(1000)
                    ->assertPathIs('/login');
                    
            // Verify the user was created in the database
            $this->assertDatabaseHas('users', [
                'email' => $uniqueEmail
            ]);
        });
    }
}