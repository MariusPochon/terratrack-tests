<?php
// Connexion à la DB
$pdo = new PDO(
    "mysql:host=db;dbname=testdb;charset=utf8",
    "root",
    "root"
);

// On récupère toutes les images
$stmt = $pdo->query("SELECT id, nom_fichier, type_mime, contenu FROM t_img");

echo "<h2>Images stockées en DB</h2>";

while ($img = $stmt->fetch()) {
    // base64_encode transforme les données binaires en texte
    // qu'on peut injecter directement dans une balise <img>
    $base64 = base64_encode($img['contenu']);
    $type = $img['type_mime'];

    echo "<div style='margin: 10px;'>";
    echo "<p>" . htmlspecialchars($img['nom_fichier']) . "</p>";
    echo "<img src='data:{$type};base64,{$base64}' width='300'/>";
    echo "</div>";
}
?>