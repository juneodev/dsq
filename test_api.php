<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Item;
use App\Http\Controllers\ItemController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Item API functionality...\n";

// Get first item
$item = Item::first();
if (!$item) {
    echo "No items found in database\n";
    exit;
}

echo "Found item with ID: " . $item->id . "\n";
echo "Current position: x={$item->x}, y={$item->y}, width={$item->width}, height={$item->height}\n";

// Test update
try {
    $controller = new ItemController();
    $request = Request::create('/api/items/' . $item->id, 'PUT', [
        'x' => 150,
        'y' => 150,
        'width' => 300,
        'height' => 200
    ]);

    $result = $controller->update($request, $item);
    echo "Success: Item updated successfully\n";

    // Refresh item from database
    $item->refresh();
    echo "New position: x={$item->x}, y={$item->y}, width={$item->width}, height={$item->height}\n";

} catch (Exception $e) {
    echo "Error updating item: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
