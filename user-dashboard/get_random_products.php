<?php
// Database connection
$servername = "localhost";
$username = "root"; // Replace with your actual DB username
$password = ""; // Replace with your actual DB password
$dbname = "fresh_farm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'status' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Get more products to fill the carousel without scrolling
$sql = "SELECT id, name, price, unit, image_path, category, stock, status 
        FROM products 
        WHERE status != 'Out of Stock' 
        ORDER BY RAND() 
        LIMIT 10"; // Increased from 8 to 10 products

$result = $conn->query($sql);
$products = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format price with the Philippine Peso symbol
        $formattedPrice = "₱" . number_format($row["price"], 2);
        
        // If the unit is per piece, don't show unit; otherwise, add unit
        $priceDisplay = $row["unit"] == "piece" ? $formattedPrice : $formattedPrice . "/" . $row["unit"];
        
        // For cleaner display, truncate long product names
        $productName = strlen($row["name"]) > 20 ? substr($row["name"], 0, 17) . '...' : $row["name"];
        
        // Prepare image path with default if missing
        $imagePath = !empty($row["image_path"]) ? $row["image_path"] : '/assets/images/placeholder.jpg';
        
        $products[] = [
            'id' => $row["id"],
            'name' => $productName,
            'original_name' => $row["name"], // Keep original name for alt text and tooltips
            'price' => $row["price"],
            'priceDisplay' => $priceDisplay,
            'unit' => $row["unit"],
            'image' => $imagePath,
            'category' => $row["category"],
            'stock' => $row["stock"],
            'status' => $row["status"]
        ];
    }
    
    echo json_encode([
        'status' => true,
        'products' => $products
    ]);
} else {
    // If no products in database, return demo products
    $sampleProducts = [
        [
            'id' => 1,
            'name' => 'Fresh Strawberries',
            'original_name' => 'Sweet Fresh Strawberries',
            'price' => 299.00,
            'priceDisplay' => '₱299.00/pack',
            'unit' => 'pack',
            'image' => 'assets/products/strawberry.jpg',
            'category' => 'Fruits',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 2,
            'name' => 'Sweet Mango',
            'original_name' => 'Fresh Sweet Mango',
            'price' => 149.00,
            'priceDisplay' => '₱149.00/kg',
            'unit' => 'kg',
            'image' => 'assets/products/mango.jpg',
            'category' => 'Fruits',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 3,
            'name' => 'Fresh Lettuce',
            'original_name' => 'Crispy Fresh Lettuce',
            'price' => 80.00,
            'priceDisplay' => '₱80.00/head',
            'unit' => 'head',
            'image' => 'assets/products/lettuce.jpg',
            'category' => 'Vegetables',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 4,
            'name' => 'Squash',
            'original_name' => 'Fresh Squash',
            'price' => 65.00,
            'priceDisplay' => '₱65.00/kg',
            'unit' => 'kg',
            'image' => 'assets/products/squash.jpg',
            'category' => 'Vegetables',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 5,
            'name' => 'Kesong Puti',
            'original_name' => 'Fresh Kesong Puti',
            'price' => 180.00,
            'priceDisplay' => '₱180.00/pack',
            'unit' => 'pack',
            'image' => 'assets/products/kesongputi.jpg',
            'category' => 'Dairy',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 6,
            'name' => 'Local Butter',
            'original_name' => 'Fresh Local Butter',
            'price' => 120.00,
            'priceDisplay' => '₱120.00/block',
            'unit' => 'block',
            'image' => 'assets/products/localbutter.jpg',
            'category' => 'Dairy',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 7,
            'name' => 'Longganisa',
            'original_name' => 'Homemade Longganisa',
            'price' => 180.00,
            'priceDisplay' => '₱180.00/pack',
            'unit' => 'pack',
            'image' => 'assets/products/longganisa.jpg',
            'category' => 'Meat',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 8,
            'name' => 'Tocino',
            'original_name' => 'Sweet Pork Tocino',
            'price' => 195.00,
            'priceDisplay' => '₱195.00/pack',
            'unit' => 'pack',
            'image' => 'assets/products/tocino.jpg',
            'category' => 'Meat',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 9,
            'name' => 'Pandesal',
            'original_name' => 'Fresh Baked Pandesal',
            'price' => 50.00,
            'priceDisplay' => '₱50.00/dozen',
            'unit' => 'dozen',
            'image' => 'assets/products/pandesal.jpg',
            'category' => 'Bakery',
            'stock' => 0,
            'status' => 'Demo Product'
        ],
        [
            'id' => 10,
            'name' => 'Ube Cheese Pandesal',
            'original_name' => 'Ube Cheese Pandesal',
            'price' => 120.00,
            'priceDisplay' => '₱120.00/pack',
            'unit' => 'pack',
            'image' => 'assets/products/ubecheesepandesal.jpg',
            'category' => 'Bakery',
            'stock' => 0,
            'status' => 'Demo Product'
        ]
    ];
    
    echo json_encode([
        'status' => true,
        'products' => $sampleProducts,
        'message' => 'No products in database - showing sample products'
    ]);
}

$conn->close();
?>