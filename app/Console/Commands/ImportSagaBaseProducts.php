<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportSagaBaseProducts extends Command
{
    protected $signature = 'saga:import-base {--file=saga_base_products.csv}';
    protected $description = 'Import SAGA Base products from CSV file';

    private $now;
    private $locale = 'en';
    private $attributeFamilyId = 1;
    private $colorAttributeId;

    public function handle()
    {
        $this->info('Starting SAGA Base products import...');
        
        $this->now = Carbon::now();
        $csvPath = "/Applications/MAMP/htdocs/nailedit_laravel/import/{$this->option('file')}";
        
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return 1;
        }

        // Get color attribute ID
        $this->colorAttributeId = DB::table('attributes')
            ->where('code', 'color')
            ->value('id');

        if (!$this->colorAttributeId) {
            $this->error('Color attribute not found!');
            return 1;
        }

        $this->info("Reading CSV: {$csvPath}");
        
        $csvData = $this->readCsv($csvPath);
        $products = $this->processCsvData($csvData);
        
        $this->info("Found " . count($products) . " products to import");
        
        DB::transaction(function () use ($products) {
            $importedCount = 0;
            
            foreach ($products as $product) {
                $this->importProduct($product);
                $importedCount++;
                
                if ($importedCount % 10 === 0) {
                    $this->info("Imported {$importedCount} products...");
                }
            }
            
            $this->info("Successfully imported {$importedCount} products!");
        });

        // Reindex products
        $this->call('indexer:index');
        
        $this->info('Import completed successfully!');
        return 0;
    }

    private function readCsv($filePath)
    {
        $csvData = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $csvData[] = array_combine($headers, $row);
            }
            
            fclose($handle);
        }
        
        return $csvData;
    }

    private function processCsvData($csvData)
    {
        $products = [];
        $parentProducts = [];
        
        // Separate parent and child products
        foreach ($csvData as $row) {
            if ($row['type'] === 'configurable') {
                $parentProducts[$row['sku']] = $row;
            } else {
                $products[] = $row;
            }
        }
        
        // Add parent info to child products
        foreach ($products as &$product) {
            $parentSku = $product['parent_sku'];
            if (isset($parentProducts[$parentSku])) {
                $product['parent_info'] = $parentProducts[$parentSku];
            }
        }
        
        return $products;
    }

    private function importProduct($productData)
    {
        if ($productData['type'] === 'configurable') {
            $this->createConfigurableProduct($productData);
        } else {
            $this->createSimpleProduct($productData);
        }
    }

    private function createConfigurableProduct($productData)
    {
        $sku = $productData['sku'];
        $urlKey = $this->generateUniqueUrlKey($productData['series']);
        
        $this->info("Creating configurable product: {$sku}");

        // Get next product ID
        $productId = DB::table('products')->max('id') + 1;

        // Create main product
        DB::table('products')->insert([
            'id' => $productId,
            'sku' => $sku,
            'type' => 'configurable',
            'attribute_family_id' => $this->attributeFamilyId,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Create flat product
        DB::table('product_flat')->insert([
            'product_id' => $productId,
            'sku' => $sku,
            'type' => 'configurable',
            'name' => $productData['series'],
            'short_description' => $productData['series'],
            'description' => $productData['series'],
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 1,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->attributeFamilyId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Insert attributes
        $this->insertAttr($productId, 'name', $productData['series'], $this->locale);
        $this->insertAttr($productId, 'short_description', $productData['series'], $this->locale);
        $this->insertAttr($productId, 'description', $productData['series'], $this->locale);
        $this->insertAttr($productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($productId, 'price', 15.00);
        $this->insertAttr($productId, 'status', 1);
        $this->insertAttr($productId, 'visible_individually', 1);
        $this->insertAttr($productId, 'new', 1);
        $this->insertAttr($productId, 'featured', 0);

        // Create category (Base category)
        $categoryId = $this->getOrCreateCategory('Base');
        
        // Link to channels, categories, inventory
        DB::table('product_channels')->insert(['product_id' => $productId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $productId, 'category_id' => $categoryId]);
        DB::table('product_inventories')->insert(['product_id' => $productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        // Create price indices
        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $productId,
                'customer_group_id' => $groupId,
                'channel_id' => 1,
                'min_price' => 15.00,
                'regular_min_price' => 15.00,
                'max_price' => 15.00,
                'regular_max_price' => 15.00,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        // Add super attribute (color)
        DB::table('product_super_attributes')->insert([
            'product_id' => $productId,
            'attribute_id' => $this->colorAttributeId,
        ]);
    }

    private function createSimpleProduct($productData)
    {
        $sku = $productData['sku'];
        $name = trim($productData['series'] . ' ' . $productData['shade']);
        $urlKey = $this->generateUniqueUrlKey($name);
        
        $this->info("Creating simple product: {$sku}");

        // Get next product ID
        $productId = DB::table('products')->max('id') + 1;

        // Create main product
        DB::table('products')->insert([
            'id' => $productId,
            'sku' => $sku,
            'type' => 'simple',
            'attribute_family_id' => $this->attributeFamilyId,
            'parent_id' => null, // Will be updated later if needed
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Create flat product
        DB::table('product_flat')->insert([
            'product_id' => $productId,
            'sku' => $sku,
            'type' => 'simple',
            'name' => $name,
            'short_description' => $name,
            'description' => $name,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 0, // Variants not visible individually
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->attributeFamilyId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Insert attributes
        $this->insertAttr($productId, 'name', $name, $this->locale);
        $this->insertAttr($productId, 'short_description', $name, $this->locale);
        $this->insertAttr($productId, 'description', $name, $this->locale);
        $this->insertAttr($productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($productId, 'price', 15.00);
        $this->insertAttr($productId, 'status', 1);
        $this->insertAttr($productId, 'visible_individually', 0);
        $this->insertAttr($productId, 'new', 1);
        $this->insertAttr($productId, 'featured', 0);

        // Add color attribute
        $colorOptionId = $this->getOrCreateColorOption($productData['shade']);
        $this->insertAttr($productId, 'color', $colorOptionId);

        // Create category
        $categoryId = $this->getOrCreateCategory('Base');
        
        // Link to channels, categories, inventory
        DB::table('product_channels')->insert(['product_id' => $productId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $productId, 'category_id' => $categoryId]);
        DB::table('product_inventories')->insert(['product_id' => $productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        // Create price indices
        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $productId,
                'customer_group_id' => $groupId,
                'channel_id' => 1,
                'min_price' => 15.00,
                'regular_min_price' => 15.00,
                'max_price' => 15.00,
                'regular_max_price' => 15.00,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        // Upload images if available
        $this->uploadProductImages($productId, $productData['product_path']);
    }

    private function getOrCreateCategory($name)
    {
        $category = DB::table('category_translations')
            ->where('name', $name)
            ->first();
            
        if ($category) {
            return $category->category_id;
        }

        // Create new category
        $categoryId = DB::table('categories')->insertGetId([
            'slug' => Str::slug($name),
            'description' => $name,
            'status' => 1,
            'display_mode' => 'products',
            'position' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Create translation
        DB::table('category_translations')->insert([
            'category_id' => $categoryId,
            'locale' => $this->locale,
            'name' => $name,
            'description' => $name,
            'slug' => Str::slug($name),
            'meta_title' => $name,
            'meta_description' => $name,
            'meta_keywords' => $name,
        ]);

        return $categoryId;
    }

    private function getOrCreateColorOption($label)
    {
        $existing = DB::table('attribute_option_translations')
            ->where('label', $label)
            ->where('locale', $this->locale)
            ->first();

        if ($existing) {
            return $existing->attribute_option_id;
        }

        // Create new color option
        $optionId = DB::table('attribute_options')->insertGetId([
            'attribute_id' => $this->colorAttributeId,
            'sort_order' => 1,
        ]);

        DB::table('attribute_option_translations')->insert([
            'attribute_option_id' => $optionId,
            'locale' => $this->locale,
            'label' => $label,
        ]);

        return $optionId;
    }

    private function insertAttr($productId, $attributeCode, $value, $locale = null)
    {
        $attribute = DB::table('attributes')
            ->where('code', $attributeCode)
            ->first();

        if (!$attribute) {
            return;
        }

        $uniqueId = uniqid();
        
        DB::table('product_attribute_values')->insert([
            'product_id' => $productId,
            'attribute_id' => $attribute->id,
            'locale' => $locale ?: $this->locale,
            'channel' => 'default',
            'integer_value' => is_numeric($value) ? $value : null,
            'float_value' => is_float($value) ? $value : null,
            'datetime_value' => null,
            'date_value' => null,
            'text_value' => is_string($value) ? $value : null,
            'unique_id' => $uniqueId,
        ]);
    }

    private function generateUniqueUrlKey($name)
    {
        $urlKey = Str::slug($name);
        $originalUrlKey = $urlKey;
        $counter = 1;

        while (DB::table('product_flat')->where('url_key', $urlKey)->exists()) {
            $urlKey = $originalUrlKey . '-' . $counter;
            $counter++;
        }

        return $urlKey;
    }

    private function uploadProductImages($productId, $productPath)
    {
        $sourcePath = "/Applications/MAMP/htdocs/nailedit_laravel/SAGA/{$productPath}";
        $targetPath = storage_path("app/public/product/{$productId}");

        if (!is_dir($sourcePath)) {
            $this->warn("Image directory not found: {$sourcePath}");
            return;
        }

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $images = glob($sourcePath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        
        foreach ($images as $image) {
            $fileName = basename($image);
            $targetFile = $targetPath . '/' . $fileName;
            
            if (copy($image, $targetFile)) {
                // Create database record for image
                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'path' => "product/{$productId}/{$fileName}",
                    'type' => 'image',
                    'position' => 1,
                ]);
                
                $this->info("Copied image: {$fileName}");
            }
        }
    }
}
