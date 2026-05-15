<?php
session_start();

// Page protégée : si pas connecté → redirige vers login
if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit;
}

// On récupère les infos de la session
$nom = $_SESSION['utilisateur']['nom'];
$id  = $_SESSION['utilisateur']['id'];

require_once 'db.php';

// Exemple de données protégées : date d'inscription de l'utilisateur
$stmt = $pdo->prepare("SELECT date_inscription FROM t_utilisateur WHERE pk_utilisateur = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Accueil — Test 10</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="carte">
    <div class="entete">
        <h1>Bonjour, <?= htmlspecialchars($nom) ?> !</h1>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="info-bloc">
        <p>Tu es connecté avec le compte <strong>#<?= $id ?></strong>.</p>
        <p>Inscription le : <strong><?= htmlspecialchars($user['date_inscription']) ?></strong></p>
    </div>

    <p style="margin-top: 24px; color: #777; font-size: 14px;">
        Cette page est protégée — elle n'est accessible qu'après connexion.
    </p>
</div>

</body>
</html>
