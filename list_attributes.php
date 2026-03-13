use Webkul\Attribute\Models\Attribute;

$attributes = Attribute::all();
echo "\n=== ALL ATTRIBUTES ===\n\n";
foreach ($attributes as $attr) {
    echo "ID: {$attr->id} | Code: {$attr->code} | Type: {$attr->type} | Configurable: " . ($attr->is_configurable ? 'Yes' : 'No') . "\n";
}
echo "\nTotal: " . $attributes->count() . " attributes\n\n";
