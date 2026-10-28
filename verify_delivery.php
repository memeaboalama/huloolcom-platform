<?php
// verify_notif_delivery.php - Verify notification delivery path for each category
require 'vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Notification Delivery Verification ===\n\n";

// Check device tokens for each category
$categories = [
    "Customers" => ["model" => "App\Domain\User\Models\User", "class" => \App\Domain\User\Models\User::class],
    "Drivers" => ["model" => "Domain\Driver\Models\Driver", "class" => \Domain\Driver\Models\Driver::class],
    "Merchants" => ["model" => "Domain\MarketPlace\Models\MerchantEmployee", "class" => \Domain\MarketPlace\Models\MerchantEmployee::class],
    "Educators" => ["model" => "Domain\Educator\Models\Educator", "class" => \Domain\Educator\Models\Educator::class],
];

// First check if educator model exists
$educatorExists = class_exists(\Domain\Educator\Models\Educator::class);

// Check device tokens count for each
$deviceTokenModel = \Domain\Notification\Models\DeviceToken::class;

echo "=== Device Token Status ===\n\n";
$totalTokens = \Domain\Notification\Models\DeviceToken::count();
echo "Total device tokens in DB: $totalTokens\n\n";

foreach ($categories as $category => $info) {
    $modelClass = $info['model'];
    $modelInstance = new $modelClass();
    $table = $modelInstance->getTable();
    
    $tokens = \Domain\Notification\Models\DeviceToken::where('deviceable_type', $modelClass)->count();
    echo "$category:\n";
    echo "  Table: $table\n";
    echo "  Device tokens: $tokens\n";
}