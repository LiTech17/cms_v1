<?php
// core/auth.php
session_start();

function checkLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>
