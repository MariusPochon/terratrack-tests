<?php
// On vérifie qu'un fichier a bien été envoyé
if (!isset($_FILES['image'])) {
    echo "Aucun fichier reçu.";
    exit;
}

$fichier = $_FILES['image'];

// $_FILES contient toutes les infos sur le fichier uploadé
// ['name']     = nom original du fichier
// ['tmp_name'] = chemin temporaire où PHP l'a stocké
// ['size']     = taille en octets
// ['error']    = 0 si pas d'erreur

if ($fichier['error'] !== 0) {
    echo "Erreur lors de l'upload : " . $fichier['error'];
    exit;
}

$pdo = new PDO(
    "mysql:host=localhost;dbname=testdb;charset=utf8",
    "root",
    "root"
);
$nom = $_FILES['image']['name'];
$type = $_FILES['image']['type'];
$contenu = file_get_contents($_FILES['image']['tmp_name']);

$sql = "INSERT INTO t_img (nom_fichier, type_mime, contenu)
        VALUES (:nom, :type, :contenu)";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':nom', $nom);
$stmt->bindParam(':type', $type);
$stmt->bindParam(':contenu', $contenu, PDO::PARAM_LOB);

$stmt->execute();

echo "Image enregistrée dans la base de données ✅";

?>