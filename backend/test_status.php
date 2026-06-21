<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Enums\SupplierAccountStatus;
use App\Http\Resources\SupplierResource;

$supplier = Supplier::first() ?: Supplier::create([
    'name' => '测试供应商B',
    'company_name' => '测试科技B有限公司',
    'business_license' => '91110000MA00000000',
    'contact_person' => '李四',
    'phone' => '13900139000',
    'email' => 'test2@example.com',
    'status' => SupplierAccountStatus::PENDING,
]);

echo "Supplier status raw value: ";
var_dump($supplier->status);

echo "\nSupplier status type: ";
echo gettype($supplier->status) . "\n";

if ($supplier->status instanceof SupplierAccountStatus) {
    echo "Is BackedEnum instance\n";
    echo "Enum value: " . $supplier->status->value . "\n";
}

$resource = new SupplierResource($supplier);
$array = $resource->toArray(request());

echo "\nResource 'status' field value: ";
var_dump($array['status']);

echo "\nResource 'status' field type: ";
echo gettype($array['status']) . "\n";

echo "\nResource array:\n";
print_r($array);

echo "\nJSON output:\n";
echo $resource->toJson(JSON_PRETTY_PRINT) . "\n";
