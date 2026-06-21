<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Supplier;
use App\Enums\SupplierAccountStatus;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

try {
    $permissions = ['supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete', 'supplier.transition'];
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    echo "Permissions OK\n";

    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions($permissions);
    echo "Role OK\n";

    $admin = User::firstOrCreate(
        ['email' => 'admin@example.com'],
        ['name' => '平台管理员', 'password' => 'password123', 'status' => 'active', 'type' => 'platform']
    );
    $admin->assignRole($adminRole);
    echo "Admin OK: admin@example.com / password123\n";

    $statuses = [SupplierAccountStatus::PENDING, SupplierAccountStatus::VERIFYING, SupplierAccountStatus::ACTIVE, SupplierAccountStatus::SUSPENDED, SupplierAccountStatus::REJECTED, SupplierAccountStatus::CANCELLED];
    $labels = ['pending' => '待审核供应商', 'verifying' => '审核中供应商', 'active' => '已激活供应商', 'suspended' => '已暂停供应商', 'rejected' => '已拒绝供应商', 'cancelled' => '已注销供应商'];

    foreach ($statuses as $status) {
        $supplier = Supplier::create([
            'company_name' => $labels[$status->value],
            'name' => $labels[$status->value],
            'status' => $status,
            'contact_person' => '张三',
            'phone' => '13800138000',
            'email' => 't' . rand(1000, 9999) . '@e.com',
            'address' => '测试地址',
            'business_license' => 'BL' . rand(1000, 9999),
            'credit_limit' => 100000,
            'balance' => 0,
        ]);
        echo "Supplier OK: {$labels[$status->value]} (ID: {$supplier->id})\n";
    }
    echo "\nDone!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
