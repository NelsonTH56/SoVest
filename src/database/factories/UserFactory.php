<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $majors = ['Computer Science', 'Business', 'Engineering', 'Biology', 'Psychology', 'Mathematics', 'English', 'Finance', 'Marketing', 'Economics'];
        $years = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate'];
        $scholarships = ['None', 'Partial', 'Full', 'Merit', 'Athletic'];
        
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            //'remember_token' => Str::random(10),
            'major' => fake()->randomElement($majors),
            'year' => fake()->randomElement($years),
            'scholarship' => fake()->randomElement($scholarships),
            'reputation_score' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}