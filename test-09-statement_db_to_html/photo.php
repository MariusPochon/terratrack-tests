<?php
require_once __DIR__ . '/db/connect.php';
// Sert une image stockée en base de données
// Usage : photo.php?id=3

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    exit;
}

$db = Connexion::getInstance();
$stmt = $db->ExecuteQuery(
    "SELECT type_mime, contenu FROM t_photo WHERE pk_photo = :id",
    [':id' => $id]
);
$photo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$photo) {
    http_response_code(404);
    exit;
}

header("Content-Type: " . $photo['type_mime']);
echo $photo['contenu'];
