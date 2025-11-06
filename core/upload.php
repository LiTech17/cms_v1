<?php
// core/upload.php
function uploadFile($file, $targetDir = "../uploads/") {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/x-icon'];
    if (!in_array($file['type'], $allowed)) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $filename = time() . '_' . basename($file['name']);
    $path = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $filename;
    }

    return null;
}
?>
