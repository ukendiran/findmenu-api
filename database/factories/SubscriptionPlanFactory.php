<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic', 'Standard', 'Premium']),
            'price' => $this->faker->randomFloat(2, 99, 9999),
            'duration_days' => $this->faker->randomElement([30, 90, 180, 365]),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
