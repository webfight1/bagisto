<?php
/**
 * Import products from staging scraper DB (aiamaailm_tooted) into Bagisto.
 *
 * Usage:
 *   php scripts/import-staging-products.php [--limit=N] [--skip-images] [--dry-run]
 *
 * Idempotent on SKU = "aiamaa-<source_id>".
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Helpers\Indexers\Price\Simple as PriceIndexer;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;

// --- args ---
$limit = null;
$skipImages = false;
$dryRun = false;
$breadcrumbLike = null;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--limit=(\d+)/', $arg, $m)) $limit = (int) $m[1];
    elseif (preg_match('/^--breadcrumb-like=(.+)/', $arg, $m)) $breadcrumbLike = $m[1];
    elseif ($arg === '--skip-images') $skipImages = true;
    elseif ($arg === '--dry-run') $dryRun = true;
}

// --- staging DB connection ---
config(['database.connections.staging' => [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'aiamaailm_tooted',
    'username' => 'root',
    'password' => 'Veebileht2025!',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
]]);

$staging = DB::connection('staging');
$bagistoDB = DB::connection();

$categoryRepo = app(CategoryRepository::class);
$productRepo  = app(ProductRepository::class);
$flatIndexer  = app(FlatIndexer::class);

$ROOT_ID    = 1;
$LOCALE     = 'en';
$LOCALE_ID  = 1;
$CHANNEL_ID = 1;
$FAMILY_ID  = 1;
$INV_SOURCE = 1;
$IMAGE_DIR  = '/var/www/aiamaailm-scraper/images';
$STORAGE_ROOT = __DIR__ . '/../storage/app/public';

// ---------- helpers ----------
function normalize(string $s): string {
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

function slugify(string $s, int $max = 80): string {
    $s = Str::slug($s, '-', 'et');
    if (!$s) $s = 'cat';
    return Str::limit($s, $max, '');
}

// ---------- category importer ----------
function buildCategoryMap($staging, $categoryRepo, $ROOT_ID, $LOCALE, $LOCALE_ID, $dryRun): array {
    $breadcrumbs = $staging->table('products')
        ->whereNotNull('breadcrumb')
        ->distinct()
        ->pluck('breadcrumb')
        ->toArray();

    // Skip subtree
    $skipFirstSegment = ['VALI PAKIAUTOMAAT', 'TÄHTIS INFO'];

    // path_key (lowercase segments) -> category_id
    $map = [];

    foreach ($breadcrumbs as $bc) {
        $segments = array_map('normalize', explode('>', $bc));
        // drop "Avaleht"
        if (!empty($segments) && mb_strtolower($segments[0]) === 'avaleht') {
            array_shift($segments);
        }
        if (empty($segments)) continue;
        if (in_array($segments[0], $skipFirstSegment, true)) continue;

        $parentId = $ROOT_ID;
        $pathSlug = '';
        $cumKey = '';
        foreach ($segments as $i => $name) {
            $name = normalize($name);
            if ($name === '') continue;
            $cumKey .= '|' . mb_strtolower($name);
            if (isset($map[$cumKey])) {
                $parentId = $map[$cumKey]['id'];
                $pathSlug = $map[$cumKey]['url_path'];
                continue;
            }

            $slug = slugify($name);
            $urlPath = $pathSlug === '' ? $slug : ($pathSlug . '/' . $slug);

            // ensure unique slug under same parent
            $existing = DB::table('category_translations')
                ->join('categories', 'categories.id', '=', 'category_translations.category_id')
                ->where('categories.parent_id', $parentId)
                ->where('category_translations.slug', $slug)
                ->where('category_translations.locale', $LOCALE)
                ->value('categories.id');
            if ($existing) {
                $map[$cumKey] = ['id' => $existing, 'url_path' => $urlPath];
                $parentId = $existing;
                $pathSlug = $urlPath;
                echo "  [cat-exists] $name (id=$existing)\n";
                continue;
            }

            if ($dryRun) {
                echo "  [DRY cat] would create '$name' under $parentId, slug=$slug, url=$urlPath\n";
                $map[$cumKey] = ['id' => -1, 'url_path' => $urlPath];
                continue;
            }

            $cat = $categoryRepo->create([
                'parent_id'    => $parentId,
                'status'       => 1,
                'position'     => 1,
                'display_mode' => 'products',
                'locale'       => $LOCALE,
                $LOCALE        => [
                    'name'        => $name,
                    'slug'        => $slug,
                    'url_path'    => $urlPath,
                    'description' => '',
                    'locale_id'   => $LOCALE_ID,
                ],
            ]);
            $map[$cumKey] = ['id' => $cat->id, 'url_path' => $urlPath];
            $parentId = $cat->id;
            $pathSlug = $urlPath;
            echo "  [cat-new] $name (id={$cat->id}, parent=$parentId)\n";
        }
    }
    return $map;
}

// Map a product breadcrumb to ALL category_ids along the path (Root + every ancestor + leaf)
function resolveCategoriesForBreadcrumb(?string $bc, array $map, int $ROOT_ID): array {
    $ids = [$ROOT_ID];
    if (!$bc) return $ids;
    $segments = array_map('normalize', explode('>', $bc));
    if (!empty($segments) && mb_strtolower($segments[0]) === 'avaleht') array_shift($segments);
    if (empty($segments)) return $ids;
    $cumKey = '';
    foreach ($segments as $s) {
        $cumKey .= '|' . mb_strtolower($s);
        if (isset($map[$cumKey])) $ids[] = $map[$cumKey]['id'];
    }
    return array_values(array_unique($ids));
}

// ---------- image picker ----------
// Given staging image rows for a source_id, pick best variant per image_id
function pickBestImages(array $rows): array {
    $sizePriority = ['thickbox' => 3, 'large' => 2, 'small' => 1];
    $byImageId = [];
    foreach ($rows as $r) {
        if (!preg_match('#/\d+-(\d+)-(large|small|thickbox)/#', $r->source_url, $m)) continue;
        $imgId = $m[1];
        $size  = $m[2];
        $score = $sizePriority[$size] ?? 0;
        if (!isset($byImageId[$imgId]) || $byImageId[$imgId]['score'] < $score) {
            $byImageId[$imgId] = ['score' => $score, 'row' => $r];
        }
    }
    return array_values(array_map(fn($x) => $x['row'], $byImageId));
}

// ---------- main ----------
echo "=== Building category map ===\n";
$catMap = buildCategoryMap($staging, $categoryRepo, $ROOT_ID, $LOCALE, $LOCALE_ID, $dryRun);
echo "Categories mapped: " . count($catMap) . "\n\n";

echo "=== Importing products" . ($limit ? " (limit $limit)" : "") . " ===\n";
$query = $staging->table('products')->orderBy('id');
if ($breadcrumbLike) $query->where('breadcrumb', 'LIKE', '%' . $breadcrumbLike . '%');
if ($limit) $query->limit($limit);
$products = $query->get();

$ok = 0; $skip = 0; $err = 0;
foreach ($products as $p) {
    $sku = 'aiamaa-' . $p->source_id;
    $existing = DB::table('products')->where('sku', $sku)->value('id');
    if ($existing) {
        echo "  [SKIP] $sku already exists (id=$existing)\n";
        $skip++;
        continue;
    }

    try {
        if ($dryRun) {
            echo "  [DRY] would import $sku ({$p->name})\n";
            $ok++;
            continue;
        }

        // 1. Create shell
        $product = $productRepo->create([
            'type'                => 'simple',
            'attribute_family_id' => $FAMILY_ID,
            'sku'                 => $sku,
        ]);

        // 2. Compose update data
        $qty = (strtolower(trim($p->availability ?? '')) === 'saadaval') ? 100 : 0;
        $urlKey = slugify($p->name) . '-' . $p->source_id;

        $shortDesc = $p->short_desc ?? '';
        $longDesc  = $p->long_desc ?: $shortDesc;
        $metaDesc  = Str::limit(strip_tags($shortDesc), 155, '');

        $categories = resolveCategoriesForBreadcrumb($p->breadcrumb, $catMap, $ROOT_ID);
        $catId = end($categories); // leaf, for log line

        $data = [
            'sku'                  => $sku,
            'name'                 => $p->name,
            'url_key'              => $urlKey,
            'status'               => 1,
            'visible_individually' => 1,
            'guest_checkout'       => 1,
            'new'                  => 0,
            'featured'             => 0,
            'tax_category_id'      => null,
            'weight'               => '0.01',
            'price'                => (string) ($p->price_eur ?? '0.00'),
            'cost'                 => null,
            'special_price'        => null,
            'special_price_from'   => null,
            'special_price_to'     => null,
            'description'          => $longDesc,
            'short_description'    => $shortDesc,
            'meta_title'           => $p->name,
            'meta_description'     => $metaDesc,
            'meta_keywords'        => '',
            'channels'             => [$CHANNEL_ID],
            'categories'           => $categories,
            'inventories'          => [$INV_SOURCE => $qty],
            'locale'               => $LOCALE,
            'channel'              => 'default',
        ];

        $product = $productRepo->update($data, $product->id);

        // Reindex flat table so /api/v1/category/* picks up the product
        $flatIndexer->refresh($product->fresh(['attribute_family','attribute_values']));

        // 3. Images (best variant per image_id)
        if (!$skipImages) {
            $imgRows = $staging->table('product_images')
                ->where('product_id', $p->id)
                ->where('downloaded', 1)
                ->orderBy('position')
                ->get()
                ->all();
            $best = pickBestImages($imgRows);
            $destDir = $STORAGE_ROOT . '/product/' . $product->id;
            if (!is_dir($destDir)) mkdir($destDir, 0775, true);
            $pos = 1;
            foreach ($best as $r) {
                if (!$r->local_path || !file_exists($r->local_path)) continue;
                $ext = pathinfo($r->local_path, PATHINFO_EXTENSION) ?: 'jpg';
                $name = Str::random(40) . '.' . $ext;
                $dest = $destDir . '/' . $name;
                if (!copy($r->local_path, $dest)) continue;
                @chmod($dest, 0664);
                DB::table('product_images')->insert([
                    'type'       => 'images',
                    'path'       => "product/{$product->id}/$name",
                    'product_id' => $product->id,
                    'position'   => $pos++,
                ]);
            }
            echo "  [OK]  $sku (id={$product->id}, cat=$catId, qty=$qty, imgs=" . ($pos - 1) . ") {$p->name}\n";
        } else {
            echo "  [OK]  $sku (id={$product->id}, cat=$catId, qty=$qty) {$p->name}\n";
        }
        $ok++;
    } catch (\Throwable $e) {
        echo "  [ERR] $sku: " . $e->getMessage() . "\n";
        $err++;
    }
}

echo "\nDone. ok=$ok, skipped=$skip, errors=$err\n";
