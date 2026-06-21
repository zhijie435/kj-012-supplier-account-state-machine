<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_no' => $this->faker->unique()->bothify('ORD-##########'),
            'distributor_id' => null,
            'supplier_id' => null,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_cost' => $this->faker->randomFloat(2, 50, 5000),
            'total_profit' => $this->faker->randomFloat(2, 10, 5000),
            'status' => Order::STATUS_PENDING_PAYMENT,
            'remark' => null,
            'created_by' => null,
            'updated_by' => null,
            'paid_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => Order::STATUS_COMPLETED]);
    }

    public function pendingPayment(): self
    {
        return $this->state(fn () => ['status' => Order::STATUS_PENDING_PAYMENT]);
    }
}
