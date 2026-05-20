<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

// This is a rough simulation for reproduction. 
// In a real scenario, I'd use a proper Pest/PHPUnit test file.
// Assuming the user wants me to identify the bug based on code analysis first.

echo "Analysis of potential issues:\n";
echo "1. The `updateProduct` method in `ProductService` iterates through `variants` from the request and calls `updateOrCreate(['size_name' => ...], [...])`.\n";
echo "2. If a user tries to change the `size_name` of an existing variant, it will create a *new* variant instead of renaming the existing one, because `updateOrCreate` uses `size_name` as the identifier.\n";
echo "3. The `variantsToDelete` logic only removes variants whose `size_name` is missing from the incoming request. So, if you rename 'Regular' to 'Standard', 'Regular' stays in the database (because it's not 'Standard') AND a new 'Standard' variant is created.\n";

echo "\nConclusion: The `size_name` should NOT be the unique identifier for `updateOrCreate`. The variant's `id` should be used if available.\n";
