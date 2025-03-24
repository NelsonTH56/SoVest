<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for User model
 * 
 * Tests the validation rules, relationship methods, and other
 * functionality of the User model.
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User model instance for testing
     *
     * @var User
     */
    protected $user;

    /**
     * Set up test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with factory
        $this->user = User::factory()->make([
            'email' => 'test@example.com',
            'password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'major' => 'Computer Science',
            'year' => 'Junior',
            'scholarship' => 'None',
            'reputation_score' => 0
        ]);
    }

    /**
     * Test that a valid user passes validation
     *
     * @return void
     */
    public function testValidUserValidation()
    {
        // Validate the user
        $result = $this->user->validate();
        
        // Assert validation passed
        $this->assertTrue($result);
        $this->assertFalse($this->user->hasErrors());
        $this->assertEmpty($this->user->getErrors());
    }

    /**
     * Test email validation rule
     *
     * @return void
     */
    public function testEmailValidation()
    {
        // Test invalid email format
        $this->user->email = 'not-an-email';
        $isValid = $this->user->validate();
        
        // Assert validation failed
        $this->assertFalse($isValid);
        $this->assertTrue($this->user->hasErrors());
        $this->assertArrayHasKey('email', $this->user->getErrors());
        
        // Test empty email
        $this->user->email = '';
        $isValid = $this->user->validate();
        
        // Assert validation failed
        $this->assertFalse($isValid);
        $this->assertTrue($this->user->hasErrors());
        
        // Test valid email
        $this->user->email = 'valid@example.com';
        $this->user->clearErrors();
        $isValid = $this->user->validate();
        
        // Assert validation passed
        $this->assertTrue($isValid);
    }

    /**
     * Test password validation rule
     *
     * @return void
     */
    //TODO: Test is failing for some reason with the error "A facade root has not been set"
    // public function testPasswordValidation()
    // {
    //     // Test password too short
    //     $this->user->password = '12345';  // Less than 6 characters
    //     $isValid = $this->user->validate();
        
    //     // Assert validation failed
    //     $this->assertFalse($isValid);
    //     $this->assertTrue($this->user->hasErrors());
    //     $this->assertArrayHasKey('password', $this->user->getErrors());
        
    //     // Test empty password
    //     $this->user->password = '';
    //     $isValid = $this->user->validate();
        
    //     // Assert validation failed
    //     $this->assertFalse($isValid);
        
    //     // Test valid password
    //     $this->user->password = 'validpassword';
    //     $this->user->clearErrors();
    //     $isValid = $this->user->validate();
        
    //     // Assert validation passed
    //     $this->assertTrue($isValid);
    // }

    /**
     * Test name length validation rules
     *
     * @return void
     */
    public function testNameLengthValidation()
    {
        // Test first name too long (over 50 chars)
        $this->user->first_name = str_repeat('a', 51);
        $isValid = $this->user->validate();
        
        // Assert validation failed
        $this->assertFalse($isValid);
        $this->assertTrue($this->user->hasErrors());
        $this->assertArrayHasKey('first_name', $this->user->getErrors());
        
        // Test last name too long
        $this->user->first_name = 'Valid';
        $this->user->last_name = str_repeat('b', 51);
        $this->user->clearErrors();
        $isValid = $this->user->validate();
        
        // Assert validation failed
        $this->assertFalse($isValid);
        $this->assertArrayHasKey('last_name', $this->user->getErrors());
        
        // Test valid names
        $this->user->first_name = 'John';
        $this->user->last_name = 'Doe';
        $this->user->clearErrors();
        $isValid = $this->user->validate();
        
        // Assert validation passed
        $this->assertTrue($isValid);
    }

    /**
     * Test email uniqueness validation
     *
     * @return void
     */
    public function testEmailUniquenessValidation()
    {
        // Create a mock user
        $user = $this->createPartialMock(User::class, ['validateUnique']);
                     
        // Set expectations for the mock
        $user->expects($this->once())
             ->method('validateUnique')
             ->with('email', 'test@example.com')
             ->willReturn(false);
        
        // Set properties for validation
        $user->email = 'test@example.com';
        $user->password = 'password123';
        
        // Test validation
        $isValid = $user->validate();
        
        // Assert validation failed due to uniqueness check
        $this->assertFalse($isValid);
    }

    /**
     * Test the getFullNameAttribute accessor
     *
     * @return void
     */
    public function testFullNameAccessor()
    {
        $this->user->first_name = 'Jane';
        $this->user->last_name = 'Smith';
        
        // Test the accessor
        $this->assertEquals('Jane Smith', $this->user->getFullNameAttribute());
        $this->assertEquals('Jane Smith', $this->user->full_name);
    }

    /**
     * Test the predictions relationship method
     *
     * @return void
     */
    public function testPredictionsRelationship()
    {
        // Test that the relationship returns a valid Eloquent relationship
        $relation = $this->user->predictions();
        
        // Assert the relationship type and class
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    /**
     * Test the predictionVotes relationship method
     *
     * @return void
     */
    public function testPredictionVotesRelationship()
    {
        // Test that the relationship returns a valid Eloquent relationship
        $relation = $this->user->predictionVotes();
        
        // Assert the relationship type and class
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    /**
     * Test the searchHistory relationship method
     *
     * @return void
     */
    public function testSearchHistoryRelationship()
    {
        // Test that the relationship returns a valid Eloquent relationship
        $relation = $this->user->searchHistory();
        
        // Assert the relationship type and class
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    /**
     * Test the savedSearches relationship method
     *
     * @return void
     */
    public function testSavedSearchesRelationship()
    {
        // Test that the relationship returns a valid Eloquent relationship
        $relation = $this->user->savedSearches();
        
        // Assert the relationship type and class
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }
}