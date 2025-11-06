<?php
// config/env_loader.php

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("The .env file not found at: " . $path);
    }
    
    // FIX 1: Replaced FILE_IGNORE_EMPTY_LINES (4) | FILE_SKIP_WHITE_SPACE (1) 
    // with their integer values for compatibility.
    // However, it's safer to just read the file and filter manually.
    $lines = file($path, 0); 
    
    foreach ($lines as $line) {
        $trimmed_line = trim($line);
        
        // Skip empty lines after trim
        if (empty($trimmed_line)) {
            continue;
        }
        
        // FIX 2: Replaced str_starts_with(trim($line), '#') with substr() check for PHP < 8.0 compatibility
        if (substr($trimmed_line, 0, 1) === '#') {
            continue;
        }
        
        // Ensure the line contains an equals sign for a valid variable
        if (strpos($trimmed_line, '=') === false) {
             continue;
        }

        list($name, $value) = explode('=', $trimmed_line, 2);
        
        $name = trim($name);
        $value = trim($value);
        
        // Only set environment variable if it hasn't been set yet
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load the environment variables from the project root
loadEnv(__DIR__ . '/../.env');

// Set configuration variables from environment
$GLOBALS['config'] = [
    'db' => [
        'host' => getenv('DB_HOST'),
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => getenv('ADMIN_USERNAME'),
        'password' => getenv('ADMIN_PASSWORD'),
    ]
];