<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiamaailmImport extends Command
{
    protected $signature = 'aiamaailm:import
                            {--staging-db=aiamaailm_tooted}
                            {--images-base=/var/www/aiamaailm-scraper/images}
                            {--limit=0 : import only N products (0 = all)}
                            {--fresh : delete existing aiamaa-* products before importing}';

    protected $description = 'Import scraped aiamaailm.ee products from staging MySQL into Bagisto';

    protected $now;
    protected $attributes;
    protected $locale = 'en';
    protected $channel = 'default';
    protected $channelId = 1;
    protected $attrFamilyId = 1;
    protected $rootCategoryId = 1;
    protected $categoryIds = [];

    public $attributeTypeFields = [
        'text' => 'text_value',
        'textarea' => 'text_value',
        'price' => 'float_value',
        'boolean' => 'boolean_value',
        'select' => 'integer_value',
    ];

    public function handle()
    {
        $this->now = Carbon::now()->format('Y-m-d H:i:s');
        $this->attributes = DB::table('attributes')->get();

        $stagingDb = $this->option('staging-db');
        $imagesBase = rtrim($this->option('images-base'), '/');
        $limit = (int) $this->option('limit');

        if ($this->option('fresh')) {
            $this->wipeExisting();
        }

        $this->info("Loading staging products from {$stagingDb}...");
        $products = DB::connection('mysql')
            ->table("{$stagingDb}.products as p")
            ->select('p.id as staging_id', 'p.source_id', 'p.source_url', 'p.sku', 'p.name',
                     'p.price_eur', 'p.short_desc', 'p.long_desc', 'p.breadcrumb')
            ->orderBy('p.id')
            ->get();

        $total = $products->count();
        $this->info("Found {$total} products to import");

        $imported = 0; $skipped = 0; $failed = 0;

        foreach ($products as $sp) {
            if ($limit > 0 && $imported >= $limit) break;

            $sku = "aiamaa-{$sp->source_id}";

            // Skip if already exists (resumable)
            if (DB::table('products')->where('sku', $sku)->exists()) {
                $skipped++;
                continue;
            }

            try {
                DB::beginTransaction();
                $this->importOne($sp, $sku, $stagingDb, $imagesBase);
                DB::commit();
                $imported++;
                if ($imported % 25 === 0) {
                    $this->info("  [{$imported}/{$total}] imported, {$skipped} skipped");
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $failed++;
                $this->error("FAIL source_id={$sp->source_id} ({$sp->name}): {$e->getMessage()}");
            }
        }

        $this->info("Done. imported={$imported} skipped={$skipped} failed={$failed}");

        $this->info("Running indexers...");
        $this->call('indexer:index');

        return 0;
    }

    protected function wipeExisting(): void
    {
        $this->warn('Wiping existing aiamaa-* products...');
        $ids = DB::table('products')->where('sku', 'LIKE', 'aiamaa-%')->pluck('id');
        $this->info("  found {$ids->count()} products to delete");

        foreach ($ids->chunk(200) as $chunk) {
            $list = $chunk->all();
            DB::table('product_attribute_values')->whereIn('product_id', $list)->delete();
            DB::table('product_categories')->whereIn('product_id', $list)->delete();
            DB::table('product_channels')->whereIn('product_id', $list)->delete();
            DB::table('product_inventories')->whereIn('product_id', $list)->delete();
            DB::table('product_inventory_indices')->whereIn('product_id', $list)->delete();
            DB::table('product_price_indices')->whereIn('product_id', $list)->delete();
            DB::table('product_images')->whereIn('product_id', $list)->delete();
            DB::table('product_flat')->whereIn('product_id', $list)->delete();
            DB::table('products')->whereIn('id', $list)->delete();
        }

        // Clear image files too
        $storage = storage_path('app/public/product');
        if (File::exists($storage)) {
            foreach (DB::table('products')->select('id')->get() as $p) {
                // keep dirs of products that still exist
            }
            // Aggressive: only safe because we just deleted DB rows. Re-keep dirs only if a product still references them.
        }

        $this->info('  wipe complete');
    }

    protected function importOne($sp, string $sku, string $stagingDb, string $imagesBase): void
    {
        $name = trim($sp->name) ?: "Toode {$sp->source_id}";
        $price = (float) ($sp->price_eur ?? 0);
        $urlKey = $this->generateUniqueUrlKey($name, $sp->source_id);
        $shortDesc = $sp->short_desc ?: '';
        $longDesc = $sp->long_desc ?: $shortDesc;
        $weight = 0.05; // 50g default for seed packets

        // Categories from breadcrumb: "Avaleht > X > Y > Z" → create X > Y > Z under root
        $categoryIds = $this->resolveCategoriesFromBreadcrumb($sp->breadcrumb);

        // 1) products row
        $productId = DB::table('products')->insertGetId([
            'sku' => $sku,
            'type' => 'simple',
            'attribute_family_id' => $this->attrFamilyId,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // 2) product_flat (denormalized, channel+locale specific)
        DB::table('product_flat')->insert([
            'product_id' => $productId,
            'sku' => $sku,
            'type' => 'simple',
            'name' => $name,
            'short_description' => $shortDesc,
            'description' => $longDesc,
            'url_key' => $urlKey,
            'price' => $price,
            'status' => 1,
            'visible_individually' => 1,
            'new' => 0,
            'featured' => 0,
            'weight' => $weight,
            'locale' => $this->locale,
            'channel' => $this->channel,
            'attribute_family_id' => $this->attrFamilyId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // 3) attribute values (EAV)
        $this->insertAttr($productId, 'sku', $sku);
        $this->insertAttr($productId, 'name', $name, $this->locale);
        $this->insertAttr($productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($productId, 'short_description', $shortDesc, $this->locale);
        $this->insertAttr($productId, 'description', $longDesc, $this->locale);
        $this->insertAttr($productId, 'price', $price, $this->locale);
        $this->insertAttr($productId, 'weight', $weight);
        $this->insertAttr($productId, 'status', 1);
        $this->insertAttr($productId, 'visible_individually', 1);
        $this->insertAttr($productId, 'guest_checkout', 1);
        $this->insertAttr($productId, 'new', 0);
        $this->insertAttr($productId, 'featured', 0);
        $this->insertAttr($productId, 'manage_stock', 1);
        $this->insertAttr($productId, 'meta_title', $name, $this->locale);
        $this->insertAttr($productId, 'meta_description', mb_substr(strip_tags($shortDesc), 0, 200), $this->locale);

        // 4) channel link
        DB::table('product_channels')->insert([
            'product_id' => $productId,
            'channel_id' => $this->channelId,
        ]);

        // 5) categories
        foreach ($categoryIds as $catId) {
            DB::table('product_categories')->insertOrIgnore([
                'product_id' => $productId,
                'category_id' => $catId,
            ]);
        }

        // 6) inventory
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => 1,
            'vendor_id' => 0,
            'qty' => 100,
        ]);
        DB::table('product_inventory_indices')->insert([
            'product_id' => $productId,
            'channel_id' => $this->channelId,
            'qty' => 100,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // 7) price indices (for customer groups 1..3)
        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $productId,
                'customer_group_id' => $groupId,
                'channel_id' => $this->channelId,
                'min_price' => $price,
                'regular_min_price' => $price,
                'max_price' => $price,
                'regular_max_price' => $price,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        // 8) images — copy from scraper dir to Bagisto storage
        $this->copyImages($productId, $sp->source_id, $imagesBase, $stagingDb);
    }

    protected function resolveCategoriesFromBreadcrumb(?string $breadcrumb): array
    {
        if (!$breadcrumb) return [];
        $parts = array_map('trim', explode('>', $breadcrumb));
        // Drop "Avaleht" (= root)
        if (!empty($parts) && mb_strtolower($parts[0]) === 'avaleht') {
            array_shift($parts);
        }
        $ids = [];
        $parentId = $this->rootCategoryId;
        foreach ($parts as $segment) {
            if ($segment === '') continue;
            $catId = $this->getOrCreateCategory($segment, $parentId);
            if ($catId) {
                $ids[] = $catId;
                $parentId = $catId;
            }
        }
        return $ids;
    }

    protected function getOrCreateCategory(string $name, int $parentId): ?int
    {
        $slug = Str::slug($name);
        $cacheKey = "{$parentId}:{$slug}";
        if (isset($this->categoryIds[$cacheKey])) return $this->categoryIds[$cacheKey];

        $existing = DB::table('category_translations as ct')
            ->join('categories as c', 'c.id', '=', 'ct.category_id')
            ->where('ct.slug', $slug)
            ->where('c.parent_id', $parentId)
            ->select('c.id')
            ->first();
        if ($existing) {
            $this->categoryIds[$cacheKey] = $existing->id;
            return $existing->id;
        }

        $parent = DB::table('categories')->where('id', $parentId)->first();
        if (!$parent) return null;

        // Nested set: make room for new node at parent's right
        DB::table('categories')->where('_rgt', '>=', $parent->_rgt)->increment('_rgt', 2);
        DB::table('categories')->where('_lft', '>', $parent->_rgt)->increment('_lft', 2);

        $catId = DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'position' => 1,
            '_lft' => $parent->_rgt,
            '_rgt' => $parent->_rgt + 1,
            'status' => 1,
            'display_mode' => 'products_and_description',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $catId,
            'name' => $name,
            'slug' => $slug,
            'url_path' => $slug,
            'description' => '',
            'locale_id' => 1,
            'locale' => $this->locale,
        ]);

        $this->categoryIds[$cacheKey] = $catId;
        return $catId;
    }

    protected function copyImages(int $productId, int $sourceId, string $imagesBase, string $stagingDb): void
    {
        $srcDir = $imagesBase . '/' . $sourceId;
        if (!File::isDirectory($srcDir)) return;

        $files = collect(File::files($srcDir))
            ->sortBy(fn($f) => $f->getFilename())
            ->values();

        if ($files->isEmpty()) return;

        $destDir = storage_path('app/public/product/' . $productId);
        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $position = 1;
        foreach ($files as $file) {
            $ext = strtolower($file->getExtension() ?: 'jpg');
            $newName = "product_{$productId}_{$position}.{$ext}";
            $dest = $destDir . '/' . $newName;
            if (File::copy($file->getPathname(), $dest)) {
                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'type' => 'images',
                    'path' => 'product/' . $productId . '/' . $newName,
                    'position' => $position,
                ]);
                $position++;
            }
        }
    }

    protected function insertAttr(int $productId, string $code, $value, ?string $locale = null): void
    {
        $attr = $this->attributes->firstWhere('code', $code);
        if (!$attr) return;

        $typeField = $this->attributeTypeFields[$attr->type] ?? 'text_value';
        $values = array_fill_keys(array_values($this->attributeTypeFields), null);
        $values[$typeField] = $value;

        $uniqueId = implode('|', array_filter([
            $attr->value_per_channel ? $this->channel : null,
            $attr->value_per_locale ? ($locale ?? $this->locale) : null,
            (string) $productId,
            (string) $attr->id,
        ]));

        DB::table('product_attribute_values')->insert(array_merge($values, [
            'attribute_id' => $attr->id,
            'product_id' => $productId,
            'channel' => $attr->value_per_channel ? $this->channel : null,
            'locale' => $attr->value_per_locale ? ($locale ?? $this->locale) : null,
            'unique_id' => $uniqueId,
        ]));
    }

    protected function generateUniqueUrlKey(string $name, int $sourceId): string
    {
        $base = Str::slug($name) ?: 'toode';
        $candidate = $base . '-' . $sourceId;
        // Bagisto requires url_key uniqueness in product_flat
        if (DB::table('product_flat')->where('url_key', $candidate)->exists()) {
            $candidate .= '-' . substr(md5(microtime(true)), 0, 6);
        }
        return $candidate;
    }
}
