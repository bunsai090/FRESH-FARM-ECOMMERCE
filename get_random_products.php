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
    // If no products in database, return sample data to avoid empty carousel
    $sampleProducts = [
        [
            'id' => 1,
            'name' => 'Organic Eggs',
            'original_name' => 'Organic Free-Range Eggs',
            'price' => 162.00,
            'priceDisplay' => '₱162.00/dozen',
            'unit' => 'dozen',
            'image' => '/assets/images/eggs.jpg',
            'category' => 'Dairy',
            'stock' => 10,
            'status' => 'In Stock'
        ],
        [
            'id' => 2,
            'name' => 'Whole Grain Bread',
            'original_name' => 'Whole Grain Bread',
            'price' => 53.00,
            'priceDisplay' => '₱53.00',
            'unit' => 'piece',
            'image' => '/assets/images/ensaymada.jpg',
            'category' => 'Bakery',
            'stock' => 15,
            'status' => 'In Stock'
        ],
        [
            'id' => 3,
            'name' => 'Fresh Carrots',
            'original_name' => 'Organic Fresh Carrots',
            'price' => 210.00,
            'priceDisplay' => '₱210.00/lb',
            'unit' => 'lb',
            'image' => '/assets/images/carrots.jpg',
            'category' => 'Vegetables',
            'stock' => 8,
            'status' => 'In Stock'
        ],
        [
            'id' => 4,
            'name' => 'Buko Pie',
            'original_name' => 'Buko Pie',
            'price' => 52.00,
            'priceDisplay' => '₱52.00',
            'unit' => 'piece',
            'image' => '/assets/images/bukopie.jpg',
            'category' => 'Bakery',
            'stock' => 5,
            'status' => 'In Stock'
        ]
    ];
    
    echo json_encode([
        'status' => true,
        'products' => $sampleProducts,
        'message' => 'Using sample products (no products in database)'
    ]);
}

$conn->close();
?>