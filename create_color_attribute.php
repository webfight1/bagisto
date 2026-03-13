use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

// Create the 46-color attribute (configurable dropdown)
$attribute = Attribute::create([
    'code' => '47-color',
    'admin_name' => '46 Color',
    'type' => 'select',
    'position' => 1,
    'is_required' => 0,
    'is_unique' => 0,
    'validation' => null,
    'value_per_locale' => 0,
    'value_per_channel' => 0,
    'is_filterable' => 1,
    'is_configurable' => 1,
    'is_visible_on_front' => 1,
    'is_user_defined' => 1,
    'is_comparable' => 0,
]);

// Add translation
DB::table('attribute_translations')->insert([
    'attribute_id' => $attribute->id,
    'locale' => 'en',
    'name' => '47 Color',
]);

echo "Created attribute: {$attribute->code} (ID: {$attribute->id})\n";

// Create dropdown options (simple numeric labels)
$colors = [
    '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
    '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
    '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
    '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
    '41', '42', '43', '44', '45', '46', '47',
];

foreach ($colors as $index => $colorNum) {
    $option = AttributeOption::create([
        'attribute_id' => $attribute->id,
        'admin_name' => $colorNum,
        'sort_order' => $index,
    ]);

    // Add translation
    DB::table('attribute_option_translations')->insert([
        'attribute_option_id' => $option->id,
        'locale' => 'en',
        'label' => $colorNum,
    ]);

    echo "Created option: {$colorNum}\n";
}

echo "\n✅ Configurable attribute '46-color' created successfully!\n";
echo "Attribute ID: {$attribute->id}\n";
echo "Total options: " . count($colors) . "\n";
echo "\nYou can now use this attribute to create configurable products.\n";

