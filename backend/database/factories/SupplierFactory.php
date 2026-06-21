<?php

namespace Database\Factories;

use App\Enums\SupplierAccountStatus;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'company_name' => $this->faker->company(),
            'business_license' => $this->faker->numerify('##########'),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'bank_name' => $this->faker->company(),
            'bank_account' => $this->faker->numerify('####################'),
            'credit_limit' => $this->faker->randomFloat(2, 1000, 100000),
            'balance' => 0,
            'status' => SupplierAccountStatus::PENDING,
            'remark' => null,
            'country_code' => 'CN',
            'tax_id' => $this->faker->numerify('##########'),
            'export_license' => $this->faker->numerify('##########'),
            'import_export_code' => $this->faker->numerify('##########'),
            'certifications' => ['ISO9001'],
            'serviced_markets' => ['China', 'USA'],
            'is_cross_border' => false,
            'verifying_at' => null,
            'activated_at' => null,
            'suspended_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
            'operated_by' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::PENDING,
            'verifying_at' => null,
            'activated_at' => null,
            'suspended_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function verifying(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::VERIFYING,
            'verifying_at' => now(),
            'activated_at' => null,
            'suspended_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::ACTIVE,
            'verifying_at' => now()->subHour(),
            'activated_at' => now(),
            'suspended_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::SUSPENDED,
            'verifying_at' => now()->subDays(2),
            'activated_at' => now()->subDay(),
            'suspended_at' => now(),
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::REJECTED,
            'verifying_at' => now()->subHour(),
            'activated_at' => null,
            'suspended_at' => null,
            'rejected_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn () => [
            'status' => SupplierAccountStatus::CANCELLED,
            'verifying_at' => now()->subDays(3),
            'activated_at' => now()->subDays(2),
            'suspended_at' => null,
            'rejected_at' => null,
            'cancelled_at' => now(),
        ]);
    }

    public function withoutLicense(): self
    {
        return $this->state(fn () => ['business_license' => null]);
    }

    public function withoutContactInfo(): self
    {
        return $this->state(fn () => [
            'contact_person' => '',
            'phone' => '',
        ]);
    }

    public function crossBorder(): self
    {
        return $this->state(fn () => ['is_cross_border' => true]);
    }
}
