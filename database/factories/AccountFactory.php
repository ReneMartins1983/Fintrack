<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Carteira', 'Banco', 'Poupança']),
            'type' => $this->faker->randomElement(array_keys(\App\Models\Account::TYPES)),
            'color' => '#7c3aed',
            'initial_balance' => $this->faker->randomFloat(2, 0, 5000),
        ];
    }
}
