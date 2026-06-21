<?php

namespace Database\Factories;

use App\Enums\SupplierAccountStatus;
use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierAccountStatusLogFactory extends Factory
{
    protected $model = SupplierAccountStatusLog::class;

    public function definition(): array
    {
        $fromStatus = $this->faker->randomElement([
            SupplierAccountStatus::PENDING,
            SupplierAccountStatus::VERIFYING,
            SupplierAccountStatus::ACTIVE,
            SupplierAccountStatus::SUSPENDED,
        ]);

        $toStatus = $this->faker->randomElement($fromStatus->allowedTransitions());

        return [
            'supplier_id' => Supplier::factory(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'remark' => $this->faker->sentence(),
            'operated_by' => User::factory(),
        ];
    }

    public function pendingToVerifying(): self
    {
        return $this->state(fn () => [
            'from_status' => SupplierAccountStatus::PENDING,
            'to_status' => SupplierAccountStatus::VERIFYING,
        ]);
    }

    public function verifyingToActive(): self
    {
        return $this->state(fn () => [
            'from_status' => SupplierAccountStatus::VERIFYING,
            'to_status' => SupplierAccountStatus::ACTIVE,
        ]);
    }

    public function activeToSuspended(): self
    {
        return $this->state(fn () => [
            'from_status' => SupplierAccountStatus::ACTIVE,
            'to_status' => SupplierAccountStatus::SUSPENDED,
        ]);
    }
}
