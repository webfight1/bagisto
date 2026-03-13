use Webkul\Attribute\Models\Attribute;

// Change this to the attribute code you want to delete
$code = '46-color';

$attr = Attribute::where('code', $code)->first();
if ($attr) {
    $attr->delete();
    echo "✅ Deleted attribute: {$code}\n";
} else {
    echo "❌ Attribute not found: {$code}\n";
}
