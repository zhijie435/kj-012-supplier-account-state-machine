<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => $this->faker->phoneNumber(),
            'avatar' => null,
            'type' => 'platform',
            'status' => 'active',
            'supplier_id' => null,
            'distributor_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function platform(): self
    {
        return $this->state(fn () => ['type' => 'platform']);
    }

    public function supplier(): self
    {
        return $this->state(fn () => ['type' => 'supplier']);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
