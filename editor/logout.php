<?php
// logout.php

// 1. Ensure the session is started to access session data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all session variables
// This removes the user's data (like role, ID, etc.)
session_unset();

// 3. Destroy the session
// This removes the session file/data from the server storage
session_destroy();

// 4. Redirect to the specified public index page
header("Location: ../public/index.php"); 
exit();
?>