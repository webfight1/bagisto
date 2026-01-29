<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportSagaProducts extends Command
{
    protected $signature = 'saga:import {--path=/Applications/MAMP/htdocs/nailedit_laravel/SAGA}';
    
    protected $description = 'Import products from SAGA directory structure into Bagisto';

    protected $categoryIds = [];
    protected $productId = 1;
    protected $now;
    protected $attributes;
    protected $locale = 'en';
    protected $sagaCategoryId;
    protected $sagaAttributeFamilyId;
    protected $shadeAttributeId;
    
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
        
        $maxProductId = DB::table('products')->max('id');
        $this->productId = $maxProductId ? $maxProductId + 1 : 1;

        $sagaPath = $this->option('path');
        
        if (!File::exists($sagaPath)) {
            $this->error("SAGA directory not found at: {$sagaPath}");
            return 1;
        }

        $this->info("Starting SAGA product import from: {$sagaPath}");
        
        $this->sagaAttributeFamilyId = 1; // Use default attribute family
        $this->shadeAttributeId = DB::table('attributes')->where('code', 'shade')->value('id');
        
        // Create SAGA category under root
        $this->sagaCategoryId = $this->getOrCreateCategory('SAGA', 1);
        $this->info("SAGA category created/found (ID: {$this->sagaCategoryId})");
        
        // SAGA folder structure: SAGA -> Categories (direct subfolders) -> Products -> Variations
        // Bagisto categories: Root -> SAGA -> Category (3 levels total)
        $categories = File::directories($sagaPath);
        
        foreach ($categories as $categoryPath) {
            $categoryName = basename($categoryPath);
            
            if ($categoryName === 'Banners') {
                continue;
            }
            
            $this->info("Processing category: {$categoryName}");
            
            // Create category under SAGA (parent_id = SAGA category ID)
            $categoryId = $this->getOrCreateCategory($categoryName, $this->sagaCategoryId);

            // Each subdirectory of category is a PRODUCT
            $productDirs = File::directories($categoryPath);

            foreach ($productDirs as $productPath) {
                $productName = basename($productPath);

                // Child folders of product are VARIATIONS (color attributes),
                // if there are none then product is simple and images are in product folder
                $variations = File::directories($productPath);
                $productImages = $this->getImages($productPath);

                // Skip completely empty folders
                if (empty($variations) && empty($productImages)) {
                    continue;
                }

                if (!empty($variations)) {
                    $this->createConfigurableProduct($productName, $categoryId, $variations);
                } else {
                    $this->createSimpleProduct($productName, $categoryId, $productImages);
                }
            }
        }

        $this->info("Import completed! Total products imported: " . ($this->productId - 1));
        
        $this->info("Reindexing products...");
        $this->call('indexer:index');
        
        return 0;
    }

    protected function createSimpleProduct($productName, $categoryId, $images)
    {
        $sku = $this->generateSku($productName);
        $urlKey = $this->generateUniqueUrlKey($productName);
        
        $this->info("  Creating simple product: {$productName} (SKU: {$sku})");

        DB::table('products')->insert([
            'id' => $this->productId,
            'sku' => $sku,
            'type' => 'simple',
            'attribute_family_id' => 1,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        DB::table('product_flat')->insert([
            'product_id' => $this->productId,
            'sku' => $sku,
            'type' => 'simple',
            'name' => $productName,
            'short_description' => $productName,
            'description' => $productName,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 1,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insertAttr($this->productId, 'name', $productName, $this->locale);
        $this->insertAttr($this->productId, 'short_description', $productName, $this->locale);
        $this->insertAttr($this->productId, 'description', $productName, $this->locale);
        $this->insertAttr($this->productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($this->productId, 'price', 15.00);
        $this->insertAttr($this->productId, 'status', 1);
        $this->insertAttr($this->productId, 'visible_individually', 1);
        $this->insertAttr($this->productId, 'new', 1);
        $this->insertAttr($this->productId, 'featured', 0);

        DB::table('product_channels')->insert(['product_id' => $this->productId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $this->productId, 'category_id' => $categoryId]);
        DB::table('product_inventories')->insert(['product_id' => $this->productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $this->productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $this->productId,
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

        $this->uploadProductImages($this->productId, $images);

        $this->productId++;
    }

    protected function createConfigurableProduct($productName, $categoryId, $variations)
    {
        $parentSku = $this->generateSku($productName);
        $urlKey = $this->generateUniqueUrlKey($productName);
        
        $this->info("  Creating configurable product: {$productName} (SKU: {$parentSku})");

        $parentProductId = $this->productId;

        DB::table('products')->insert([
            'id' => $parentProductId,
            'sku' => $parentSku,
            'type' => 'configurable',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $parentFlatId = DB::table('product_flat')->insertGetId([
            'product_id' => $parentProductId,
            'sku' => $parentSku,
            'type' => 'configurable',
            'name' => $productName,
            'short_description' => $productName,
            'description' => $productName,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 1,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insertAttr($parentProductId, 'name', $productName, $this->locale);
        $this->insertAttr($parentProductId, 'short_description', $productName, $this->locale);
        $this->insertAttr($parentProductId, 'description', $productName, $this->locale);
        $this->insertAttr($parentProductId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($parentProductId, 'price', 15.00);
        $this->insertAttr($parentProductId, 'status', 1);
        $this->insertAttr($parentProductId, 'visible_individually', 1);
        $this->insertAttr($parentProductId, 'new', 1);
        $this->insertAttr($parentProductId, 'featured', 0);

        DB::table('product_channels')->insert(['product_id' => $parentProductId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $parentProductId, 'category_id' => $categoryId]);
        
        DB::table('product_super_attributes')->insert([
            'product_id' => $parentProductId,
            'attribute_id' => $this->shadeAttributeId,
        ]);

        $this->productId++;

        // Get first variation's first image for parent product
        $firstVariationImage = null;
        foreach ($variations as $variationPath) {
            $variationImages = $this->getImages($variationPath);
            if (!empty($variationImages)) {
                $firstVariationImage = $variationImages[0];
                break;
            }
        }

        // Upload first image to parent product
        if ($firstVariationImage) {
            $this->uploadProductImages($parentProductId, [$firstVariationImage]);
        }

        foreach ($variations as $variationPath) {
            $variationName = basename($variationPath);
            $variationImages = $this->getImages($variationPath);
            
            if (empty($variationImages)) {
                continue;
            }

            $this->createVariant($parentProductId, $parentFlatId, $productName, $variationName, $categoryId, $variationImages);
        }

        $minPrice = 15.00;
        $maxPrice = 15.00;

        DB::table('product_inventories')->insert(['product_id' => $parentProductId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $parentProductId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $parentProductId,
                'customer_group_id' => $groupId,
                'channel_id' => 1,
                'min_price' => $minPrice,
                'regular_min_price' => $minPrice,
                'max_price' => $maxPrice,
                'regular_max_price' => $maxPrice,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    protected function createVariant($parentProductId, $parentFlatId, $productName, $variationName, $categoryId, $images)
    {
        $variantSku = $this->generateSku($productName . ' ' . $variationName);
        $urlKey = Str::slug($productName . ' ' . $variationName);
        $fullName = $productName . ' - ' . $variationName;
        $colorLabel = $productName . ' ' . $variationName;
        
        $this->info("    Creating variant: {$variationName} (SKU: {$variantSku})");

        DB::table('products')->insert([
            'id' => $this->productId,
            'sku' => $variantSku,
            'type' => 'simple',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => $parentProductId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        DB::table('product_flat')->insert([
            'product_id' => $this->productId,
            'sku' => $variantSku,
            'type' => 'simple',
            'name' => $fullName,
            'short_description' => $fullName,
            'description' => $fullName,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 0,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => $parentFlatId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insertAttr($this->productId, 'sku', $variantSku);
        $this->insertAttr($this->productId, 'name', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'short_description', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'description', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($this->productId, 'price', 15.00);
        $this->insertAttr($this->productId, 'weight', 0.1);
        $this->insertAttr($this->productId, 'status', 1);
        $this->insertAttr($this->productId, 'visible_individually', 0);
        $this->insertAttr($this->productId, 'new', 1);
        $this->insertAttr($this->productId, 'featured', 0);
        
        $shadeOptionId = $this->getOrCreateShadeOption($colorLabel);
        $this->insertAttr($this->productId, 'shade', $shadeOptionId);

        DB::table('product_channels')->insert(['product_id' => $this->productId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $this->productId, 'category_id' => $categoryId]);
        DB::table('product_inventories')->insert(['product_id' => $this->productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $this->productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $this->productId,
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

        $this->uploadProductImages($this->productId, $images);

        $this->productId++;
    }

    protected function uploadProductImages($productId, $images)
    {
        if (empty($images)) {
            return;
        }

        $position = 1;
        
        foreach ($images as $imagePath) {
            $fileName = basename($imagePath);
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $newFileName = 'product_' . $productId . '_' . $position . '.' . strtolower($extension);
            
            $storagePath = storage_path('app/public/product/' . $productId);
            
            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }
            
            $destinationFile = $storagePath . '/' . $newFileName;
            
            if (File::copy($imagePath, $destinationFile)) {
                $relativePath = 'product/' . $productId . '/' . $newFileName;
                
                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'type' => 'images',
                    'path' => $relativePath,
                    'position' => $position,
                ]);
                
                $this->info("      Uploaded image: {$fileName}");
                $position++;
            }
        }
    }

    protected function getImages($path)
    {
        $images = [];
        $files = File::files($path);
        
        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = $file->getPathname();
            }
        }
        
        return $images;
    }

    protected function getOrCreateCategory($categoryName, $parentId = 1)
    {
        $slug = Str::slug($categoryName);
        
        if (isset($this->categoryIds[$slug])) {
            return $this->categoryIds[$slug];
        }

        $existing = DB::table('category_translations')
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            $this->categoryIds[$slug] = $existing->category_id;
            return $existing->category_id;
        }

        $parent = DB::table('categories')->where('id', $parentId)->first();
        
        if (!$parent) {
            $this->error("Parent category with ID {$parentId} not found!");
            return null;
        }

        DB::table('categories')
            ->where('_rgt', '>=', $parent->_rgt)
            ->increment('_rgt', 2);
        
        DB::table('categories')
            ->where('_lft', '>', $parent->_rgt)
            ->increment('_lft', 2);

        $lft = $parent->_rgt;
        $rgt = $parent->_rgt + 1;

        $categoryId = DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'position' => 1,
            '_lft' => $lft,
            '_rgt' => $rgt,
            'status' => 1,
            'display_mode' => 'products_and_description',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $categoryId,
            'locale' => $this->locale,
            'name' => $categoryName,
            'slug' => $slug,
            'description' => $categoryName,
            'meta_title' => $categoryName,
            'meta_description' => $categoryName,
            'meta_keywords' => $slug,
        ]);

        $this->categoryIds[$slug] = $categoryId;
        
        return $categoryId;
    }

    protected function groupProductsByBaseName($products)
    {
        $grouped = [];
        
        foreach ($products as $productPath) {
            $productName = basename($productPath);
            $baseName = $this->extractBaseName($productName);
            
            if (!isset($grouped[$baseName])) {
                $grouped[$baseName] = [];
            }
            
            $grouped[$baseName][] = [
                'path' => $productPath,
                'name' => $productName,
                'number' => $this->extractNumber($productName)
            ];
        }
        
        return $grouped;
    }

    protected function extractBaseName($productName)
    {
        if (preg_match('/^(.+?)\s+(\d+)\s*$/', $productName, $matches)) {
            return trim($matches[1]);
        }
        return $productName;
    }

    protected function extractNumber($productName)
    {
        if (preg_match('/^(.+?)\s+(\d+)\s*$/', $productName, $matches)) {
            return $matches[2];
        }
        return null;
    }

    protected function createConfigurableProductFromNumberedFolders($baseName, $categoryId, $productGroup)
    {
        $parentSku = $this->generateSku($baseName);
        $urlKey = $this->generateUniqueUrlKey($baseName);
        
        $this->info("  Creating configurable product from numbered folders: {$baseName} (SKU: {$parentSku})");

        $parentProductId = $this->productId;

        DB::table('products')->insert([
            'id' => $parentProductId,
            'sku' => $parentSku,
            'type' => 'configurable',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $parentFlatId = DB::table('product_flat')->insertGetId([
            'product_id' => $parentProductId,
            'sku' => $parentSku,
            'type' => 'configurable',
            'name' => $baseName,
            'short_description' => $baseName,
            'description' => $baseName,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 1,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insertAttr($parentProductId, 'name', $baseName, $this->locale);
        $this->insertAttr($parentProductId, 'short_description', $baseName, $this->locale);
        $this->insertAttr($parentProductId, 'description', $baseName, $this->locale);
        $this->insertAttr($parentProductId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($parentProductId, 'price', 15.00);
        $this->insertAttr($parentProductId, 'status', 1);
        $this->insertAttr($parentProductId, 'visible_individually', 1);
        $this->insertAttr($parentProductId, 'new', 1);
        $this->insertAttr($parentProductId, 'featured', 0);

        DB::table('product_channels')->insert(['product_id' => $parentProductId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $parentProductId, 'category_id' => $categoryId]);
        
        DB::table('product_super_attributes')->insert([
            'product_id' => $parentProductId,
            'attribute_id' => $this->shadeAttributeId,
        ]);

        $this->productId++;

        foreach ($productGroup as $variant) {
            $variantImages = $this->getImages($variant['path']);
            
            if (empty($variantImages)) {
                continue;
            }

            $variantNumber = $variant['number'];
            $this->createVariantFromNumberedFolder($parentProductId, $parentFlatId, $baseName, $variantNumber, $categoryId, $variantImages);
        }

        $minPrice = 15.00;
        $maxPrice = 15.00;

        DB::table('product_inventories')->insert(['product_id' => $parentProductId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $parentProductId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $parentProductId,
                'customer_group_id' => $groupId,
                'channel_id' => 1,
                'min_price' => $minPrice,
                'regular_min_price' => $minPrice,
                'max_price' => $maxPrice,
                'regular_max_price' => $maxPrice,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    protected function createVariantFromNumberedFolder($parentProductId, $parentFlatId, $baseName, $variantNumber, $categoryId, $images)
    {
        $variantSku = $this->generateSku($baseName . ' ' . $variantNumber);
        $urlKey = Str::slug($baseName . ' ' . $variantNumber);
        $fullName = $baseName . ' - ' . $variantNumber;
        $colorLabel = $baseName . ' ' . $variantNumber;
        
        $this->info("    Creating variant: {$variantNumber} (SKU: {$variantSku})");

        DB::table('products')->insert([
            'id' => $this->productId,
            'sku' => $variantSku,
            'type' => 'simple',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => $parentProductId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        DB::table('product_flat')->insert([
            'product_id' => $this->productId,
            'sku' => $variantSku,
            'type' => 'simple',
            'name' => $fullName,
            'short_description' => $fullName,
            'description' => $fullName,
            'url_key' => $urlKey,
            'price' => 15.00,
            'status' => 1,
            'visible_individually' => 0,
            'new' => 1,
            'featured' => 0,
            'weight' => 0.1,
            'locale' => $this->locale,
            'channel' => 'default',
            'attribute_family_id' => $this->sagaAttributeFamilyId,
            'parent_id' => $parentFlatId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insertAttr($this->productId, 'sku', $variantSku);
        $this->insertAttr($this->productId, 'name', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'short_description', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'description', $fullName, $this->locale);
        $this->insertAttr($this->productId, 'url_key', $urlKey, $this->locale);
        $this->insertAttr($this->productId, 'price', 15.00);
        $this->insertAttr($this->productId, 'weight', 0.1);
        $this->insertAttr($this->productId, 'status', 1);
        $this->insertAttr($this->productId, 'visible_individually', 0);
        $this->insertAttr($this->productId, 'new', 1);
        $this->insertAttr($this->productId, 'featured', 0);
        
        $shadeOptionId = $this->getOrCreateShadeOption($colorLabel);
        $this->insertAttr($this->productId, 'shade', $shadeOptionId);

        DB::table('product_channels')->insert(['product_id' => $this->productId, 'channel_id' => 1]);
        DB::table('product_categories')->insert(['product_id' => $this->productId, 'category_id' => $categoryId]);
        DB::table('product_inventories')->insert(['product_id' => $this->productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
        DB::table('product_inventory_indices')->insert(['product_id' => $this->productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $this->now, 'updated_at' => $this->now]);

        for ($groupId = 1; $groupId <= 3; $groupId++) {
            DB::table('product_price_indices')->insert([
                'product_id' => $this->productId,
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

        $this->uploadProductImages($this->productId, $images);

        $this->productId++;
    }

    protected function generateSku($name)
    {
        $sku = strtoupper(Str::slug($name, '-'));
        $sku = str_replace('-', '', $sku);
        $sku = substr($sku, 0, 20);
        
        $counter = 1;
        $originalSku = $sku;
        
        while (DB::table('products')->where('sku', $sku)->exists()) {
            $sku = $originalSku . $counter;
            $counter++;
        }
        
        return $sku;
    }

    protected function getOrCreateSagaAttributeFamily()
    {
        $existing = DB::table('attribute_families')->where('code', 'saga')->first();
        
        if ($existing) {
            return $existing->id;
        }

        $familyId = DB::table('attribute_families')->insertGetId([
            'code' => 'saga',
            'name' => 'SAGA Products',
            'status' => 1,
            'is_user_defined' => 1,
        ]);

        $generalGroupId = DB::table('attribute_groups')->insertGetId([
            'attribute_family_id' => $familyId,
            'name' => 'General',
            'position' => 1,
            'is_user_defined' => 0,
        ]);

        $requiredAttributes = [1, 2, 3, 4, 5, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 26, 27];
        
        foreach ($requiredAttributes as $position => $attrId) {
            DB::table('attribute_group_mappings')->insert([
                'attribute_id' => $attrId,
                'attribute_group_id' => $generalGroupId,
                'position' => $position + 1,
            ]);
        }

        $colorAttributeId = DB::table('attributes')->where('code', 'color')->value('id');
        if ($colorAttributeId) {
            DB::table('attribute_group_mappings')->insert([
                'attribute_id' => $colorAttributeId,
                'attribute_group_id' => $generalGroupId,
                'position' => count($requiredAttributes) + 1,
            ]);
        }

        return $familyId;
    }

    protected function getOrCreateShadeOption($label)
    {
        // Extract number from label (e.g., "Shimmer Base 10" -> 10, "15" -> 15)
        preg_match('/(\d+)$/', trim($label), $matches);
        $number = isset($matches[1]) ? (int)$matches[1] : null;
        
        // If label ends with a number between 1-20, use existing shade option
        if ($number !== null && $number >= 1 && $number <= 20) {
            // Find shade option with label matching the number
            $existing = DB::table('attribute_option_translations')
                ->where('label', (string)$number)
                ->where('locale', $this->locale)
                ->join('attribute_options', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
                ->where('attribute_options.attribute_id', $this->shadeAttributeId)
                ->first();
            
            if ($existing) {
                $this->info("  Using existing shade option: {$number}");
                return $existing->attribute_option_id;
            }
        }
        
        // Otherwise, check if exact label exists
        $existing = DB::table('attribute_option_translations')
            ->where('label', $label)
            ->where('locale', $this->locale)
            ->first();

        if ($existing) {
            return $existing->attribute_option_id;
        }

        // Create new option only if not found
        $optionId = DB::table('attribute_options')->insertGetId([
            'attribute_id' => $this->shadeAttributeId,
            'admin_name' => $label,
            'sort_order' => 0,
            'swatch_value' => null,
        ]);

        DB::table('attribute_option_translations')->insert([
            'attribute_option_id' => $optionId,
            'locale' => $this->locale,
            'label' => $label,
        ]);

        $this->info("  Created new shade option: {$label}");
        return $optionId;
    }

    protected function generateUniqueUrlKey($name)
    {
        $baseUrlKey = Str::slug($name);
        $urlKey = $baseUrlKey;
        $counter = 1;

        while (true) {
            $categoryExists = DB::table('category_translations')->where('slug', $urlKey)->exists();
            $productExists = DB::table('product_flat')->where('url_key', $urlKey)->exists();
            
            if (!$categoryExists && !$productExists) {
                break;
            }
            
            $urlKey = $baseUrlKey . '-product-' . $counter;
            $counter++;
        }

        return $urlKey;
    }

    protected function insertAttr($productId, $attributeCode, $value, $locale = null)
    {
        $attribute = $this->attributes->where('code', $attributeCode)->first();
        if (!$attribute) return;

        $attributeTypeValues = array_fill_keys(array_values($this->attributeTypeFields), null);
        $typeField = $this->attributeTypeFields[$attribute->type] ?? 'text_value';

        $uniqueId = implode('|', array_filter([
            $attribute->value_per_channel ? 'default' : null,
            $attribute->value_per_locale ? $locale : null,
            $productId,
            $attribute->id,
        ]));

        DB::table('product_attribute_values')->insert(array_merge($attributeTypeValues, [
            'attribute_id' => $attribute->id,
            'product_id' => $productId,
            $typeField => $value,
            'channel' => $attribute->value_per_channel ? 'default' : null,
            'locale' => $attribute->value_per_locale ? $locale : null,
            'unique_id' => $uniqueId,
        ]));
    }
}
