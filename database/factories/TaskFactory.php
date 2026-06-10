<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Automatically links a fresh user if one isn't specified
            'title' => $this->faker->sentence(3),
            'is_completed' => false,
        ];
    }
}
