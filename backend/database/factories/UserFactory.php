<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'student_id' => '2024-'.fake()->unique()->numerify('#####'),
            'email' => fake()->unique()->safeEmail(),
            'college_program' => fake()->randomElement([
                'BS Computer Science',
                'BS Business Administration',
                'BS Nursing',
                'BA Communication',
                'BS Civil Engineering',
            ]),
            'contact_number' => fake()->numerify('09#########'),
            'role' => 'student',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
