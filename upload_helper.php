<?php

function handleImageUpload(string $fieldName, string $subfolder): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }


    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return null;
    }

    $ext = $allowed[$mime];
    $targetDir = __DIR__ . '/img/' . $subfolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return 'img/' . $subfolder . '/' . $filename;
}
