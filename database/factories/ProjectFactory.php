<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+1 year');
        
        return [
            'name' => fake()->words(3, true) . 'プロジェクト',
            'description' => fake()->paragraph(),
            'project_key' => 'PRJ-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => fake()->randomElement(['planning', 'active', 'completed', 'on_hold', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'progress' => fake()->numberBetween(0, 100),
            'owner_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * プロジェクトの状態を進行中に設定
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'progress' => fake()->numberBetween(10, 80),
        ]);
    }

    /**
     * プロジェクトの状態を完了に設定
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    /**
     * プロジェクトの状態を計画中に設定
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
            'progress' => 0,
        ]);
    }
}