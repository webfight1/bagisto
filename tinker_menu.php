use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;

echo "\n=== BAGISTO TINKER MENU ===\n\n";
echo "1. List all attributes\n";
echo "2. List all products\n";
echo "3. Delete attribute by code\n";
echo "4. Delete product by SKU\n";
echo "5. Show attribute details\n";
echo "6. Show product details\n";
echo "7. List all attribute options for an attribute\n";
echo "8. Delete all products\n";
echo "9. Exit\n\n";

$choice = readline("Enter your choice (1-9): ");

switch ($choice) {
    case '1':
        // List all attributes
        $attributes = Attribute::all();
        echo "\n=== ATTRIBUTES ===\n";
        foreach ($attributes as $attr) {
            echo "ID: {$attr->id} | Code: {$attr->code} | Type: {$attr->type} | Configurable: " . ($attr->is_configurable ? 'Yes' : 'No') . "\n";
        }
        echo "\nTotal: " . $attributes->count() . " attributes\n";
        break;

    case '2':
        // List all products
        $products = Product::all();
        echo "\n=== PRODUCTS ===\n";
        foreach ($products as $product) {
            echo "ID: {$product->id} | SKU: {$product->sku} | Type: {$product->type}\n";
        }
        echo "\nTotal: " . $products->count() . " products\n";
        break;

    case '3':
        // Delete attribute by code
        $code = readline("Enter attribute code to delete: ");
        $attr = Attribute::where('code', $code)->first();
        if ($attr) {
            $attr->delete();
            echo "✅ Deleted attribute: {$code}\n";
        } else {
            echo "❌ Attribute not found: {$code}\n";
        }
        break;

    case '4':
        // Delete product by SKU
        $sku = readline("Enter product SKU to delete: ");
        $product = Product::where('sku', $sku)->first();
        if ($product) {
            $product->delete();
            echo "✅ Deleted product: {$sku}\n";
        } else {
            echo "❌ Product not found: {$sku}\n";
        }
        break;

    case '5':
        // Show attribute details
        $code = readline("Enter attribute code: ");
        $attr = Attribute::where('code', $code)->first();
        if ($attr) {
            echo "\n=== ATTRIBUTE DETAILS ===\n";
            echo "ID: {$attr->id}\n";
            echo "Code: {$attr->code}\n";
            echo "Admin Name: {$attr->admin_name}\n";
            echo "Type: {$attr->type}\n";
            echo "Configurable: " . ($attr->is_configurable ? 'Yes' : 'No') . "\n";
            echo "Filterable: " . ($attr->is_filterable ? 'Yes' : 'No') . "\n";
            echo "Required: " . ($attr->is_required ? 'Yes' : 'No') . "\n";
            
            $options = AttributeOption::where('attribute_id', $attr->id)->get();
            echo "\nOptions: " . $options->count() . "\n";
            foreach ($options as $opt) {
                echo "  - {$opt->admin_name}\n";
            }
        } else {
            echo "❌ Attribute not found: {$code}\n";
        }
        break;

    case '6':
        // Show product details
        $sku = readline("Enter product SKU: ");
        $product = Product::where('sku', $sku)->first();
        if ($product) {
            echo "\n=== PRODUCT DETAILS ===\n";
            echo "ID: {$product->id}\n";
            echo "SKU: {$product->sku}\n";
            echo "Type: {$product->type}\n";
            echo "Parent ID: " . ($product->parent_id ?? 'None') . "\n";
            echo "Attribute Family ID: {$product->attribute_family_id}\n";
        } else {
            echo "❌ Product not found: {$sku}\n";
        }
        break;

    case '7':
        // List attribute options
        $code = readline("Enter attribute code: ");
        $attr = Attribute::where('code', $code)->first();
        if ($attr) {
            $options = AttributeOption::where('attribute_id', $attr->id)->get();
            echo "\n=== OPTIONS FOR '{$code}' ===\n";
            foreach ($options as $opt) {
                echo "ID: {$opt->id} | Admin Name: {$opt->admin_name} | Sort: {$opt->sort_order}\n";
            }
            echo "\nTotal: " . $options->count() . " options\n";
        } else {
            echo "❌ Attribute not found: {$code}\n";
        }
        break;

    case '8':
        // Delete all products
        $confirm = readline("Are you sure you want to delete ALL products? (yes/no): ");
        if (strtolower($confirm) === 'yes') {
            DB::table('product_images')->delete();
            DB::table('product_super_attributes')->delete();
            DB::table('product_inventories')->delete();
            DB::table('product_inventory_indices')->delete();
            DB::table('product_price_indices')->delete();
            DB::table('product_categories')->delete();
            DB::table('product_channels')->delete();
            DB::table('product_attribute_values')->delete();
            DB::table('product_flat')->delete();
            DB::table('products')->delete();
            echo "✅ All products deleted successfully\n";
        } else {
            echo "❌ Cancelled\n";
        }
        break;

    case '9':
        echo "Goodbye!\n";
        exit;
        break;

    default:
        echo "Invalid choice!\n";
        break;
}

echo "\n";
