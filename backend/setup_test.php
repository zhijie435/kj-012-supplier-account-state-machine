<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Supplier;
use App\Enums\SupplierAccountStatus;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$permissions = [
    'supplier.view',
    'supplier.create',
    'supplier.edit',
    'supplier.delete',
    'supplier.transition',
];

foreach ($permissions as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
}

$adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
$adminRole->syncPermissions($permissions);

$admin = User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => '平台管理员',
        'password' => 'password123',
        'status' => 'active',
        'type' => 'platform',
    ]
);
$admin->assignRole($adminRole);
echo "Created admin: admin@example.com / password123\n";

$statuses = [
    SupplierAccountStatus::PENDING,
    SupplierAccountStatus::VERIFYING,
    SupplierAccountStatus::ACTIVE,
    SupplierAccountStatus::SUSPENDED,
    SupplierAccountStatus::REJECTED,
    SupplierAccountStatus::CANCELLED,
];

$labels = [
    'pending' => '待审核供应商',
    'verifying' => '审核中供应商',
    'active' => '已激活供应商',
    'suspended' => '已暂停供应商',
    'rejected' => '已拒绝供应商',
    'cancelled' => '已注销供应商',
];

foreach ($statuses as $status) {
    $supplier = Supplier::create([
        'company_name' => $labels[$status->value],
        'name' => $labels[$status->value],
        'status' => $status,
        'contact_person' => '张三',
        'phone' => '13800138000',
        'email' => 'test' . rand(1000, 9999) . '@example.com',
        'address' => '测试地址',
        'business_license' => '91110000MA00' . rand(1000, 9999),
        'credit_limit' => 100000,
        'balance' => 0,
    ]);
    echo "Created supplier: {$labels[$status->value]} (ID: {$supplier->id}, Status: {$supplier->status->value})\n";
}

echo "\nSetup complete!\n";
