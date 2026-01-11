<?php
// Test logic convert URL -> Method Name
$urlPart = 'search-product-for-disposal';
$method = str_replace('-', '', lcfirst(ucwords($urlPart, '-')));

echo "URL: $urlPart\n";
echo "Method: $method\n";

if ($method === 'searchProductForDisposal') {
    echo "✅ Logic convert ĐÚNG!\n";
} else {
    echo "❌ Logic convert SAI! Kết quả: '$method'\n";
    echo "Mong đợi: 'searchProductForDisposal'\n";
}

// Test PHP version
echo "PHP Version: " . phpversion() . "\n";
