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

// On vérifie que c'est bien une image
$typesAutorises = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($fichier['type'], $typesAutorises)) {
    echo "Type de fichier non autorisé.";
    exit;
}

// On génère un nom unique pour éviter les collisions
$nomFichier = uniqid() . '_' . basename($fichier['name']);
$destination = 'uploads/' . $nomFichier;

// On déplace le fichier du dossier temporaire vers uploads/
if (move_uploaded_file($fichier['tmp_name'], $destination)) {
    echo "Upload réussi !<br>";
    echo "<img src='" . $destination . "' width='300'/>";
} else {
    echo "Erreur lors de la sauvegarde.";
}
?>