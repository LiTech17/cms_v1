<?php
// config/database.php

// 1. Load the environment variables first
require_once __DIR__ . '/env_loader.php';

// 2. Retrieve database configuration from the global config array
// that was populated by env_loader.php
$config = $GLOBALS['config']['db'];

$host = $config['host'];
$db   = $config['name'];
$user = $config['user'];
$pass = $config['pass'];
$charset = $config['charset'];

// 3. Set up the DSN and PDO options as before
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Establish the PDO connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Optional: for confirmation during development
    // echo "Successfully connected to the database: $db\n"; 
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}