<?php
/**
 * Seed placeholder images for all products that have none.
 * Run: php scripts/seed-product-images.php
 *
 * Downloads ~600x600 images from picsum.photos (seeded per product for stability),
 * saves to storage/app/public/product/<id>/<id>.jpg,
 * inserts row into product_images.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$products = DB::table('products')
    ->leftJoin('product_images', 'products.id', '=', 'product_images.product_id')
    ->whereNull('product_images.id')
    ->select('products.id', 'products.sku')
    ->get();

echo "Found {$products->count()} products without images\n";

$storageRoot = __DIR__ . '/../storage/app/public';

foreach ($products as $p) {
    $dir = "$storageRoot/product/{$p->id}";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = "{$p->id}.jpg";
    $localPath = "$dir/$filename";
    $relPath   = "product/{$p->id}/$filename";

    // picsum.photos with seed = product id ensures stable, varied images
    $url = "https://picsum.photos/seed/aiamaa{$p->id}/600/600";

    $ctx = stream_context_create(['http' => ['timeout' => 15, 'follow_location' => 1]]);
    $img = @file_get_contents($url, false, $ctx);

    if ($img === false || strlen($img) < 1000) {
        echo "  [SKIP] {$p->id}: download failed\n";
        continue;
    }

    file_put_contents($localPath, $img);
    @chmod($localPath, 0664);

    DB::table('product_images')->insert([
        'type'       => 'images',
        'path'       => $relPath,
        'product_id' => $p->id,
        'position'   => 1,
    ]);

    echo "  [OK]   {$p->id}: $relPath (" . round(strlen($img) / 1024) . "KB)\n";
}

echo "Done.\n";
