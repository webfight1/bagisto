<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NailTechProductSeeder extends Seeder
{
    protected $categoryIds = [];

    public $attributeTypeFields = [
        'text' => 'text_value',
        'textarea' => 'text_value',
        'price' => 'float_value',
        'boolean' => 'boolean_value',
        'select' => 'integer_value',
    ];

    public function run($parameters = [])
    {
        $defaultLocale = $parameters['default_locale'] ?? 'en';
        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];
        $now = Carbon::now()->format('Y-m-d H:i:s');

        $this->createCategories($locales, $now);
        $this->createProducts($locales, $now);

        echo "Nail tech products seeded!\n";
    }

    protected function createCategories($locales, $now)
    {
        $categories = [
            ['name' => 'Geellakid', 'slug' => 'geellakid', 'position' => 1],
            ['name' => 'UV-geelid', 'slug' => 'uv-geelid', 'position' => 2],
            ['name' => 'Baas- ja pealislakid', 'slug' => 'baas-ja-pealislakid', 'position' => 3],
            ['name' => 'Küünetööriistad', 'slug' => 'kyunetooriistad', 'position' => 4],
            ['name' => 'Elektrilised seadmed', 'slug' => 'elektrilised-seadmed', 'position' => 5],
            ['name' => 'Maniküüri hooldusvahendid', 'slug' => 'manikyuri-hooldusvahendid', 'position' => 6],
            ['name' => 'Küünetipud ja ehitusmaterjalid', 'slug' => 'kyunetipud-ja-ehitusmaterjalid', 'position' => 7],
            ['name' => 'Nail Art dekoratsioonid', 'slug' => 'nail-art-dekoratsioonid', 'position' => 8],
            ['name' => 'Puhastus- ja desinfitseerimisvahendid', 'slug' => 'puhastus-ja-desinfitseerimisvahendid', 'position' => 9],
            ['name' => 'Lambid ja UV/LED valgustid', 'slug' => 'lambid-ja-uv-led-valgustid', 'position' => 10],
        ];

        $lft = 2;

        foreach ($categories as $category) {
            $id = DB::table('categories')->insertGetId([
                'parent_id' => 1,
                'position' => $category['position'],
                '_lft' => $lft,
                '_rgt' => $lft + 1,
                'status' => 1,
                'display_mode' => 'products_and_description',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->categoryIds[$category['slug']] = $id;

            foreach ($locales as $locale) {
                DB::table('category_translations')->insert([
                    'category_id' => $id,
                    'locale' => $locale,
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['name'],
                    'meta_title' => $category['name'],
                    'meta_description' => $category['name'],
                    'meta_keywords' => $category['slug'],
                ]);
            }

            $lft += 2;
        }

        DB::table('categories')->where('id', 1)->update(['_rgt' => $lft]);
    }

    protected function createProducts($locales, $now)
    {
        require __DIR__ . '/NailTechProductsData.php';
        $products = getNailTechProducts();
        $attributes = DB::table('attributes')->get();
        $productId = 1;

        foreach ($products as $productData) {
            DB::table('products')->insert([
                'id' => $productId,
                'sku' => $productData['sku'],
                'type' => 'simple',
                'attribute_family_id' => 1,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($locales as $locale) {
                DB::table('product_flat')->insert([
                    'product_id' => $productId,
                    'sku' => $productData['sku'],
                    'type' => 'simple',
                    'name' => $productData['name'],
                    'short_description' => $productData['short_description'],
                    'description' => $productData['description'],
                    'url_key' => $productData['url_key'],
                    'price' => $productData['price'],
                    'status' => 1,
                    'visible_individually' => 1,
                    'new' => $productData['new'] ?? 0,
                    'featured' => $productData['featured'] ?? 0,
                    'weight' => 0.5,
                    'locale' => $locale,
                    'channel' => 'default',
                    'attribute_family_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($locales as $locale) {
                $this->insertAttr($productId, 'name', $productData['name'], $attributes, $locale);
                $this->insertAttr($productId, 'short_description', $productData['short_description'], $attributes, $locale);
                $this->insertAttr($productId, 'description', $productData['description'], $attributes, $locale);
                $this->insertAttr($productId, 'url_key', $productData['url_key'], $attributes, $locale);
            }

            $this->insertAttr($productId, 'price', $productData['price'], $attributes);
            $this->insertAttr($productId, 'status', 1, $attributes);
            $this->insertAttr($productId, 'visible_individually', 1, $attributes);
            $this->insertAttr($productId, 'new', $productData['new'] ?? 0, $attributes);
            $this->insertAttr($productId, 'featured', $productData['featured'] ?? 0, $attributes);

            DB::table('product_channels')->insert(['product_id' => $productId, 'channel_id' => 1]);

            if (! empty($productData['category_slug']) && isset($this->categoryIds[$productData['category_slug']])) {
                DB::table('product_categories')->insert([
                    'product_id' => $productId,
                    'category_id' => $this->categoryIds[$productData['category_slug']],
                ]);
            }
            DB::table('product_inventories')->insert(['product_id' => $productId, 'inventory_source_id' => 1, 'vendor_id' => 0, 'qty' => 100]);
            DB::table('product_inventory_indices')->insert(['product_id' => $productId, 'channel_id' => 1, 'qty' => 100, 'created_at' => $now, 'updated_at' => $now]);

            for ($groupId = 1; $groupId <= 3; $groupId++) {
                DB::table('product_price_indices')->insert([
                    'product_id' => $productId,
                    'customer_group_id' => $groupId,
                    'channel_id' => 1,
                    'min_price' => $productData['price'],
                    'regular_min_price' => $productData['price'],
                    'max_price' => $productData['price'],
                    'regular_max_price' => $productData['price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $productId++;
        }
    }

    protected function insertAttr($productId, $attributeCode, $value, $attributes, $locale = null)
    {
        $attribute = $attributes->where('code', $attributeCode)->first();
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
