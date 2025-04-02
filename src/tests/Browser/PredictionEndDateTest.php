<?php

namespace Tests\Browser;

use App\Models\User;
//use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PredictionEndDateTest extends DuskTestCase
{
    //use DatabaseMigrations;

    /**
     * Test validation for weekend date selection
     */
    public function testWeekendDateValidation(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            // Find next Saturday
            $saturday = $this->getNextSaturday();
            
            $browser->loginAs($user)
                    ->visit('/predictions/create')
                    ->assertSee('Create New Stock Prediction')
                    ->type('#end_date', $saturday)
                    ->waitFor('.is-invalid') // Wait for validation to occur
                    ->assertPresent('#end_date.is-invalid')
                    ->assertSee('must be a business day');
        });
    }

    /**
     * Test validation for date beyond 5 business days
     */
    public function testBeyondFiveBusinessDayValidation(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            // Get date 6 business days from now
            $futureDate = $this->addBusinessDays(new \DateTime(), 6);
            
            $browser->loginAs($user)
                    ->visit('/predictions/create')
                    ->assertSee('Create New Stock Prediction')
                    ->type('#end_date', $futureDate->format('Y-m-d'))
                    ->waitFor('.is-invalid') // Wait for validation to occur
                    ->assertPresent('#end_date.is-invalid')
                    ->assertSee('within 5 business days');
        });
    }

    /**
     * Test validation for valid business day date
     */
    public function testValidBusinessDayDate(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            // Get date 3 business days from now
            $validDate = $this->addBusinessDays(new \DateTime(), 3);
            
            $browser->loginAs($user)
                    ->visit('/predictions/create')
                    ->assertSee('Create New Stock Prediction')
                    ->type('#end_date', $validDate->format('Y-m-d'))
                    ->waitFor('.is-valid') // Wait for validation to occur
                    ->assertMissing('#end_date.is-invalid')
                    ->assertPresent('#end_date.is-valid');
        });
    }

    /**
     * Test form submission with valid date
     */
    public function testFormSubmissionWithValidDate(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            // Get date 3 business days from now
            $validDate = $this->addBusinessDays(new \DateTime(), 3);
            
            $browser->loginAs($user)
                    ->visit('/predictions/create')
                    ->assertSee('Create New Stock Prediction')
                    // Fill in all required fields with valid data
                    ->type('#stock-search', 'AAPL')
                    ->waitFor('#stock-suggestions .list-group-item')
                    ->click('#stock-suggestions .list-group-item')
                    ->select('#prediction_type', 'Bullish')
                    ->type('#end_date', $validDate->format('Y-m-d'))
                    ->type('#reasoning', 'This is a test prediction with sufficient detail for validation purposes.')
                    // Verify all fields are valid before submission
                    ->waitFor('#end_date.is-valid')
                    ->assertPresent('#prediction_type.is-valid')
                    ->assertPresent('#reasoning.is-valid')
                    // Submit the form
                    ->press('Create Prediction')
                    // Verify no validation errors for end date
                    ->assertMissing('#end_date.is-invalid');
        });
    }

    /**
     * Get the next Saturday's date
     *
     * @return string Date in Y-m-d format
     */
    private function getNextSaturday(): string
    {
        $date = new \DateTime();
        
        // Find days until next Saturday (day 6)
        $daysUntilSaturday = (6 - $date->format('w') + 7) % 7;
        if ($daysUntilSaturday == 0) {
            $daysUntilSaturday = 7; // If today is Saturday, get next Saturday
        }
        
        $date->modify("+{$daysUntilSaturday} days");
        return $date->format('Y-m-d');
    }

    /**
     * Add a specified number of business days to a date
     *
     * @param \DateTime $date Starting date
     * @param int $days Number of business days to add
     * @return \DateTime Resulting date
     */
    private function addBusinessDays(\DateTime $date, int $days): \DateTime
    {
        $i = 0;
        while ($i < $days) {
            $date->modify('+1 day');
            // Skip weekends
            if ($this->isBusinessDay($date)) {
                $i++;
            }
        }
        return $date;
    }

    /**
     * Check if a date is a business day
     *
     * @param \DateTime $date Date to check
     * @return bool Whether it's a business day
     */
    private function isBusinessDay(\DateTime $date): bool
    {
        $dayOfWeek = (int)$date->format('w');
        // 0 (Sunday) to 6 (Saturday), so 1-5 are business days
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }
}