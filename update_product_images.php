<?php
require_once 'connect.php';

// Array mapping product names to their correct image files
$imageMap = [
    'Eggs' => 'eggs.jpg',
    'apple' => 'apple.jpg',
    'Ensaymada' => 'spanishbread.jpg', // Using similar bread image
    'chicken' => 'tinola.jpg', // Using related image
    'Avocado' => 'avocado.jpg',
    'Petchay' => 'waterspinach.jpg' // Using similar vegetable image
];

// Update each product's image path
foreach ($imageMap as $productName => $imagePath) {
    $sql = "UPDATE products SET image_path = ? WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $fullPath = 'assets/products/' . $imagePath;
    $stmt->bind_param("ss", $fullPath, $productName);
    
    if ($stmt->execute()) {
        echo "Updated image path for $productName to $fullPath<br>";
    } else {
        echo "Error updating $productName: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

$conn->close();
echo "Image path update complete!";
?> 