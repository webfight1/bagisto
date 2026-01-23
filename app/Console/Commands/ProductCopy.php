<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCopy extends Command
{
    protected $signature = 'products:copy 
                           {--source= : Source product ID to copy from}
                           {--name= : New product name (optional)}
                           {--sku= : New product SKU (optional)}
                           {--price= : New product price (optional)}
                           {--variant-prefix= : Prefix for variant names (optional)}
                           {--copy-images=1 : Copy product images}';

    protected $description = 'Copy a product and its variants with all related data';

    protected $now;
    protected $attributes;
    protected $locale = 'en';
    
    public $attributeTypeFields = [
        'text' => 'text_value',
        'textarea' => 'text_value',
        'price' => 'float_value',
        'boolean' => 'boolean_value',
        'select' => 'integer_value',
        'checkbox' => 'text_value',
        'multiselect' => 'text_value',
        'datetime' => 'datetime_value',
        'date' => 'date_value',
    ];

    public function handle()
    {
        $this->now = Carbon::now()->format('Y-m-d H:i:s');
        $this->attributes = DB::table('attributes')->get();

        $sourceId = $this->option('source');
        if (!$sourceId) {
            $this->error('Source product ID is required. Use --source=123');
            return 1;
        }

        // Get source product
        $sourceProduct = DB::table('products')->where('id', $sourceId)->first();
        if (!$sourceProduct) {
            $this->error("Source product with ID {$sourceId} not found");
            return 1;
        }

        $this->info("Copying product: {$sourceProduct->sku} (ID: {$sourceId})");

        // Start transaction
        DB::beginTransaction();
        try {
            if ($sourceProduct->type === 'configurable') {
                $newProductId = $this->copyConfigurableProduct($sourceProduct);
            } else {
                $newProductId = $this->copySimpleProduct($sourceProduct);
            }

            DB::commit();
            $this->info("Product copied successfully! New product ID: {$newProductId}");
            
            // Reindex products
            $this->call('indexer:index');
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error copying product: " . $e->getMessage());
            return 1;
        }
    }

    protected function copySimpleProduct($sourceProduct)
    {
        // Get source product flat data
        $sourceFlat = DB::table('product_flat')
            ->where('product_id', $sourceProduct->id)
            ->first();

        if (!$sourceFlat) {
            throw new \Exception("Source product flat data not found for product ID: {$sourceProduct->id}");
        }

        // Generate new SKU and name
        $newSku = $this->option('sku') ?? $this->generateUniqueSku($sourceProduct->sku);
        $newName = $this->option('name') ?? $sourceFlat->name . ' - Copy';
        $newUrlKey = $this->generateUniqueUrlKey($newName);

        // Get new product ID
        $maxProductId = DB::table('products')->max('id');
        $newProductId = $maxProductId + 1;

        // Copy main product record
        DB::table('products')->insert([
            'id' => $newProductId,
            'sku' => $newSku,
            'type' => $sourceProduct->type,
            'attribute_family_id' => $sourceProduct->attribute_family_id,
            'parent_id' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Copy product_flat record
        $flatData = (array) $sourceFlat;
        $flatData['product_id'] = $newProductId;
        $flatData['sku'] = $newSku;
        $flatData['name'] = $newName;
        $flatData['url_key'] = $newUrlKey;
        $flatData['created_at'] = $this->now;
        $flatData['updated_at'] = $this->now;

        // Update price if provided
        if ($this->option('price')) {
            $flatData['price'] = $this->option('price');
        }

        // Unset ID to avoid conflicts
        unset($flatData['id']);
        
        DB::table('product_flat')->insert($flatData);

        // Copy attributes
        $this->copyProductAttributes($sourceProduct->id, $newProductId, $newName, $newUrlKey);

        // Copy categories
        $this->copyProductCategories($sourceProduct->id, $newProductId);

        // Copy channels
        $this->copyProductChannels($sourceProduct->id, $newProductId);

        // Copy inventory
        $this->copyProductInventory($sourceProduct->id, $newProductId);

        // Copy price indices
        $this->copyProductPriceIndices($sourceProduct->id, $newProductId);

        // Copy images if requested
        if ($this->option('copy-images')) {
            $this->copyProductImages($sourceProduct->id, $newProductId);
        }

        return $newProductId;
    }

    protected function copyConfigurableProduct($sourceProduct)
    {
        // Copy the parent configurable product
        $newParentId = $this->copySimpleProduct($sourceProduct);

        // Copy super attributes
        $this->copySuperAttributes($sourceProduct->id, $newParentId);

        // Get and copy variants
        $variants = DB::table('products')
            ->where('parent_id', $sourceProduct->id)
            ->get();

        foreach ($variants as $variant) {
            $this->copyVariant($variant, $newParentId);
        }

        return $newParentId;
    }

    protected function copyVariant($sourceVariant, $newParentId)
    {
        // Get source variant flat data
        $sourceFlat = DB::table('product_flat')
            ->where('product_id', $sourceVariant->id)
            ->first();

        if (!$sourceFlat) {
            throw new \Exception("Source variant flat data not found for product ID: {$sourceVariant->id}");
        }

        // Generate new SKU and name
        $newSku = $this->generateUniqueSku($sourceVariant->sku);
        
        // Use variant prefix if provided, otherwise use default
        $variantPrefix = $this->option('variant-prefix');
        if ($variantPrefix) {
            $newName = $variantPrefix . ' - ' . $sourceFlat->name;
        } else {
            $newName = $sourceFlat->name . ' - Copy';
        }
        
        $newUrlKey = $this->generateUniqueUrlKey($newName);

        // Get new variant ID
        $maxProductId = DB::table('products')->max('id');
        $newVariantId = $maxProductId + 1;

        // Copy variant record
        DB::table('products')->insert([
            'id' => $newVariantId,
            'sku' => $newSku,
            'type' => $sourceVariant->type,
            'attribute_family_id' => $sourceVariant->attribute_family_id,
            'parent_id' => $newParentId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // Copy product_flat record
        $flatData = (array) $sourceFlat;
        $flatData['product_id'] = $newVariantId;
        $flatData['sku'] = $newSku;
        $flatData['name'] = $newName;
        $flatData['url_key'] = $newUrlKey;
        $flatData['created_at'] = $this->now;
        $flatData['updated_at'] = $this->now;
        unset($flatData['id']);

        DB::table('product_flat')->insert($flatData);

        // Copy attributes
        $this->copyProductAttributes($sourceVariant->id, $newVariantId, $newName, $newUrlKey);

        // Copy categories
        $this->copyProductCategories($sourceVariant->id, $newVariantId);

        // Copy channels
        $this->copyProductChannels($sourceVariant->id, $newVariantId);

        // Copy inventory
        $this->copyProductInventory($sourceVariant->id, $newVariantId);

        // Copy price indices
        $this->copyProductPriceIndices($sourceVariant->id, $newVariantId);

        // Copy images if requested
        if ($this->option('copy-images')) {
            $this->copyProductImages($sourceVariant->id, $newVariantId);
        }
    }

    protected function copyProductAttributes($sourceId, $newId, $newName, $newUrlKey)
    {
        $attributes = DB::table('product_attribute_values')
            ->where('product_id', $sourceId)
            ->get();

        $newPrice = $this->option('price');

        foreach ($attributes as $attr) {
            $attrData = (array) $attr;
            $attrData['product_id'] = $newId;
            unset($attrData['id']);

            // Generate new unique_id for the copied product
            $attribute = $this->attributes->where('id', $attrData['attribute_id'])->first();
            if ($attribute) {
                $uniqueIdParts = array_filter([
                    $attribute->value_per_channel ? 'default' : null,
                    $attribute->value_per_locale ? $this->locale : null,
                    $newId,
                    $attrData['attribute_id'],
                ]);
                $attrData['unique_id'] = implode('|', $uniqueIdParts);
                
                $typeField = $this->attributeTypeFields[$attribute->type] ?? 'text_value';
                
                // Update name attribute (usually ID 1)
                if ($attribute->code === 'name') {
                    $attrData[$typeField] = $newName;
                }
                // Update url_key attribute (usually ID 4)
                if ($attribute->code === 'url_key') {
                    $attrData[$typeField] = $newUrlKey;
                }
                // Update price attribute if provided
                if ($attribute->code === 'price' && $newPrice) {
                    $attrData[$typeField] = $newPrice;
                }
            }

            DB::table('product_attribute_values')->insert($attrData);
        }
    }

    protected function copyProductCategories($sourceId, $newId)
    {
        $categories = DB::table('product_categories')
            ->where('product_id', $sourceId)
            ->get();

        foreach ($categories as $category) {
            $categoryData = (array) $category;
            $categoryData['product_id'] = $newId;
            unset($categoryData['id']);
            DB::table('product_categories')->insert($categoryData);
        }
    }

    protected function copyProductChannels($sourceId, $newId)
    {
        $channels = DB::table('product_channels')
            ->where('product_id', $sourceId)
            ->get();

        foreach ($channels as $channel) {
            $channelData = (array) $channel;
            $channelData['product_id'] = $newId;
            unset($channelData['id']);
            DB::table('product_channels')->insert($channelData);
        }
    }

    protected function copyProductInventory($sourceId, $newId)
    {
        $inventory = DB::table('product_inventories')
            ->where('product_id', $sourceId)
            ->first();

        if ($inventory) {
            $inventoryData = (array) $inventory;
            $inventoryData['product_id'] = $newId;
            unset($inventoryData['id']);
            DB::table('product_inventories')->insert($inventoryData);
        }

        // Copy inventory indices
        $indices = DB::table('product_inventory_indices')
            ->where('product_id', $sourceId)
            ->get();

        foreach ($indices as $index) {
            $indexData = (array) $index;
            $indexData['product_id'] = $newId;
            $indexData['created_at'] = $this->now;
            $indexData['updated_at'] = $this->now;
            unset($indexData['id']);
            DB::table('product_inventory_indices')->insert($indexData);
        }
    }

    protected function copyProductPriceIndices($sourceId, $newId)
    {
        $indices = DB::table('product_price_indices')
            ->where('product_id', $sourceId)
            ->get();

        $newPrice = $this->option('price');

        foreach ($indices as $index) {
            $indexData = (array) $index;
            $indexData['product_id'] = $newId;
            $indexData['created_at'] = $this->now;
            $indexData['updated_at'] = $this->now;
            unset($indexData['id']);

            // Update price if provided
            if ($newPrice) {
                $indexData['min_price'] = $newPrice;
                $indexData['max_price'] = $newPrice;
                $indexData['regular_min_price'] = $newPrice;
                $indexData['regular_max_price'] = $newPrice;
            }

            DB::table('product_price_indices')->insert($indexData);
        }
    }

    protected function copyProductImages($sourceId, $newId)
    {
        $images = DB::table('product_images')
            ->where('product_id', $sourceId)
            ->orderBy('position')
            ->get();

        foreach ($images as $image) {
            $sourcePath = $image->path;
            
            // Create new directory for copied images
            $newDirectory = dirname($sourcePath);
            $sourceFilename = basename($sourcePath);
            $newFilename = 'copy_' . $sourceFilename;
            $newPath = $newDirectory . '/' . $newFilename;

            // Copy physical file
            if (Storage::disk('public')->exists($sourcePath)) {
                $sourceFullPath = Storage::disk('public')->path($sourcePath);
                $newFullPath = Storage::disk('public')->path($newPath);
                
                // Ensure directory exists
                $newDir = dirname($newFullPath);
                if (!File::exists($newDir)) {
                    File::makeDirectory($newDir, 0755, true);
                }

                if (File::copy($sourceFullPath, $newFullPath)) {
                    // Insert new image record
                    $imageData = (array) $image;
                    $imageData['product_id'] = $newId;
                    $imageData['path'] = $newPath;
                    unset($imageData['id']);
                    DB::table('product_images')->insert($imageData);
                    
                    $this->info("  Copied image: {$sourceFilename} -> {$newFilename}");
                }
            }
        }
    }

    protected function copySuperAttributes($sourceId, $newId)
    {
        $superAttrs = DB::table('product_super_attributes')
            ->where('product_id', $sourceId)
            ->get();

        foreach ($superAttrs as $attr) {
            $attrData = (array) $attr;
            $attrData['product_id'] = $newId;
            unset($attrData['id']);
            DB::table('product_super_attributes')->insert($attrData);
        }
    }

    protected function generateUniqueSku($originalSku)
    {
        $counter = 1;
        $newSku = $originalSku . '-COPY-' . $counter;

        while (DB::table('products')->where('sku', $newSku)->exists()) {
            $counter++;
            $newSku = $originalSku . '-COPY-' . $counter;
        }

        return $newSku;
    }

    protected function generateUniqueUrlKey($name)
    {
        $baseUrlKey = Str::slug($name);
        $urlKey = $baseUrlKey;
        $counter = 1;

        while (DB::table('product_flat')->where('url_key', $urlKey)->exists()) {
            $urlKey = $baseUrlKey . '-copy-' . $counter;
            $counter++;
        }

        return $urlKey;
    }
}
