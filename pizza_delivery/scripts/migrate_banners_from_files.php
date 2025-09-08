<?php
// Simple migration: import files from public/uploads/banners into banners table as image_data
require_once __DIR__ . '/../config/db.php';

$uploadDir = __DIR__ . '/../public/uploads/banners/';
$files = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (empty($files)) {
    echo "No banner files found in: $uploadDir\n";
    exit(0);
}

foreach ($files as $file) {
    $basename = basename($file);
    echo "Importing: $basename... ";

    $mime = mime_content_type($file);
    $data = @file_get_contents($file);
    if ($data === false) {
        echo "FAILED (read)\n";
        continue;
    }

    // Check if file already imported by looking for identical blob size or name in title
    $stmt = $pdo->prepare("SELECT id FROM banners WHERE LENGTH(image_data) = ? LIMIT 1");
    $stmt->execute([strlen($data)]);
    $exists = $stmt->fetch();
    if ($exists) {
        echo "SKIPPED (already exists)\n";
        continue;
    }

    // Find next position
    $posStmt = $pdo->query("SELECT MAX(position) as max_pos FROM banners");
    $result = $posStmt->fetch();
    $pos = ($result['max_pos'] ?? 0) + 1;

    $title = pathinfo($basename, PATHINFO_FILENAME);
    try {
        $insert = $pdo->prepare("INSERT INTO banners (title, image_mime, image_data, link, position, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $insert->bindParam(1, $title);
        $insert->bindParam(2, $mime);
        $insert->bindParam(3, $data, PDO::PARAM_LOB);
        $insert->bindParam(4, $nullLink);
        $insert->bindParam(5, $pos);
        $nullLink = null;
        $insert->execute();
        echo "OK (id=" . $pdo->lastInsertId() . ")\n";
    } catch (PDOException $e) {
        echo "FAILED (db: " . $e->getMessage() . ")\n";
    }
}

echo "Done.\n";
