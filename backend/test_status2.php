<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Enums\SupplierAccountStatus;

$supplier = Supplier::first() ?: Supplier::create([
    'name' => '测试供应商C',
    'company_name' => '测试科技C有限公司',
    'business_license' => '91110000MA00000001',
    'contact_person' => '王五',
    'phone' => '13700137000',
    'email' => 'test3@example.com',
    'status' => SupplierAccountStatus::PENDING,
]);

echo "=== Supplier status field test ===\n";
echo "Supplier status value: ";
var_dump($supplier->status);
echo "Type: " . gettype($supplier->status) . "\n";

if ($supplier->status instanceof SupplierAccountStatus) {
    echo "Is SupplierAccountStatus enum\n";
    echo "Enum->value: " . $supplier->status->value . "\n";
}

echo "\n=== json_encode on status ===\n";
echo json_encode(['status' => $supplier->status], JSON_PRETTY_PRINT) . "\n";

echo "\n=== json_encode on status->value ===\n";
echo json_encode(['status' => $supplier->status->value], JSON_PRETTY_PRINT) . "\n";

echo "\n=== Laravel toArray() on model ===\n";
$arr = $supplier->toArray();
var_dump($arr['status'] ?? 'NOT SET');
echo "Type: " . gettype($arr['status'] ?? null) . "\n";

echo "\n=== SupplierAccountStatus enum jsonSerialize test ===\n";
$enum = SupplierAccountStatus::PENDING;
echo "jsonSerialize result: ";
var_dump($enum->jsonSerialize());
echo "json_encode enum: " . json_encode($enum) . "\n";

echo "\n=== Model->getRawOriginal test ===\n";
echo "getRawOriginal('status'): ";
var_dump($supplier->getRawOriginal('status'));
