<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonth();

        return [
            'businessId' => Business::factory(), // or existing Business ID
            'planId' => SubscriptionPlan::factory(), // or existing Plan ID
            'payment_gateway' => 'phonepe',
            'paymentId' => $this->faker->uuid,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'is_active' => true,
        ];
    }
}
