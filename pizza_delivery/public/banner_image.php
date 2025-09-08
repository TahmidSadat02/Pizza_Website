<?php
/**
 * Stream banner image bytes from DB by id
 */

require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Bad Request');
}

try {
    $stmt = $pdo->prepare('SELECT image_mime, image_data FROM banners WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['image_data'])) {
        // Return a 1x1 transparent png as fallback
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');
        exit;
    }

    $mime = $row['image_mime'] ?: 'application/octet-stream';
    $data = $row['image_data'];

    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . strlen($data));
    echo $data;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    error_log('banner_image error: ' . $e->getMessage());
    exit('Server Error');
}
