<?php
$host = 'localhost';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS fresh1";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db('fresh1');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Products table created successfully<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_date DATE,
    shipping_address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Orders table created successfully<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Insert sample products
$sql = "INSERT INTO products (product_name, description, price, stock) VALUES
    ('Sample Product 1', 'Description for product 1', 29.99, 100),
    ('Sample Product 2', 'Description for product 2', 49.99, 50),
    ('Sample Product 3', 'Description for product 3', 99.99, 25)";

if ($conn->query($sql) === TRUE) {
    echo "Sample products inserted successfully<br>";
} else {
    echo "Error inserting sample products: " . $conn->error . "<br>";
}

// Insert a sample user (for testing)
$password_hash = password_hash('password123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, email, password) VALUES ('testuser', 'test@example.com', '$password_hash')";

if ($conn->query($sql) === TRUE) {
    echo "Sample user created successfully<br>";
} else {
    if ($conn->errno == 1062) { // Duplicate entry error
        echo "Sample user already exists<br>";
    } else {
        echo "Error creating sample user: " . $conn->error . "<br>";
    }
}

$conn->close();

echo "<br>Setup complete! You can now go back to the orders page.";
?> 