<?php
// config/db_setup.php

// 1. Load the environment variables and configuration
require_once __DIR__ . '/env_loader.php'; 

$db_config = $GLOBALS['config']['db'];
$admin_config = $GLOBALS['config']['admin'];

$host = $db_config['host'];
$db_name = $db_config['name'];
$user = $db_config['user'];
$pass = $db_config['pass'];
$charset = $db_config['charset'];

// Path to the full SQL dump file
// ADJUSTED PATH: Points to db_init.sql inside the same 'config' folder
$sql_file_path = __DIR__ . '/db_init.sql'; 

// --- Connection and Execution Logic ---

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 2. Establish the PDO connection
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Database connection successful.\n";

    // 3. Read and Execute Schema & Seed Data
    echo "--- Starting Schema and Data Load ---\n";
    $sql_content = file_get_contents($sql_file_path);

    if ($sql_content === false) {
        // Improved error message to reflect the new expected location
        die("❌ Error: Could not read the SQL schema file. Ensure 'db_init.sql' is in the **config** folder: " . realpath(__DIR__) . "\n");
    }

    // Split SQL into individual statements for execution
    $sql_statements = array_filter(array_map('trim', explode(';', $sql_content)));

    $pdo->beginTransaction();
    
    // Execute all statements (CREATE, INSERT, ALTER, etc.)
    foreach ($sql_statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement . ';');
        }
    }
    
    echo "✅ Schema and initial seed data loaded successfully.\n";

    // 4. ⭐ SECURE ADMIN PASSWORD UPDATE/CREATION ⭐
    $admin_username = $admin_config['username'];
    $admin_raw_password = $admin_config['password'];
    
    // Generate the secure BCRYPT hash
    $secure_password_hash = password_hash($admin_raw_password, PASSWORD_DEFAULT);
    
    // Update the seed admin user (ID 1)
    $update_sql = "
        UPDATE admins 
        SET username = :new_username, 
            password = :new_password 
        WHERE id = 1;
    ";
    
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([
        ':new_username' => $admin_username,
        ':new_password' => $secure_password_hash
    ]);
    
    echo "✅ Admin user updated and securely hashed (User: **{$admin_username}**).\n";

    $pdo->commit();
    echo "\n--- Database Initialization Complete! ---\n";
    echo "Initial Admin Login:\n";
    echo "  Username: **{$admin_username}**\n";
    echo "  Password: **{$admin_raw_password}**\n";
    echo "\n⚠️ **CRITICAL ACTION REQUIRED:** Manually remove the **ADMIN_PASSWORD** from your `.env` file now!\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n\n❌ Initialization Failed:\n";
    die("Error: " . $e->getMessage() . "\n");
}