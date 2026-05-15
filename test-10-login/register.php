<?php
session_start();

// Si déjà connecté, pas besoin de s'inscrire
if (isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit;
}

$erreur  = '';
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $nom   = trim($_POST['nom_utilisateur'] ?? '');
    $email = trim($_POST['email']           ?? '');
    $mdp   = $_POST['mot_de_passe']         ?? '';
    $mdp2  = $_POST['mot_de_passe2']        ?? '';

    // --- Validations ---
    if (!$nom || !$email || !$mdp || !$mdp2) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (strlen($nom) < 3) {
        $erreur = "Le nom d'utilisateur doit faire au moins 3 caractères.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail n'est pas valide.";

    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères.";

    } elseif ($mdp !== $mdp2) {
        $erreur = "Les deux mots de passe ne correspondent pas.";

    } else {
        // Vérifie que le nom ou l'email n'est pas déjà pris
        $stmt = $pdo->prepare("SELECT pk_utilisateur FROM t_utilisateur WHERE nom_utilisateur = :nom OR email = :email");
        $stmt->execute([':nom' => $nom, ':email' => $email]);

        if ($stmt->fetch()) {
            $erreur = "Ce nom d'utilisateur ou cet e-mail est déjà utilisé.";
        } else {
            // password_hash() hache le mot de passe avec bcrypt
            // On ne stocke JAMAIS le mot de passe en clair
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO t_utilisateur (nom_utilisateur, email, mot_de_passe)
                VALUES (:nom, :email, :hash)
            ");
            $stmt->execute([':nom' => $nom, ':email' => $email, ':hash' => $hash]);

            $succes = "Compte créé ! Tu peux maintenant te connecter.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Inscription — Test 10</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="carte">
    <h1>Créer un compte</h1>

    <?php if ($erreur): ?>
        <div class="alerte erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alerte succes"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nom d'utilisateur</label>
        <input type="text" name="nom_utilisateur" required minlength="3"
               value="<?= htmlspecialchars($_POST['nom_utilisateur'] ?? '') ?>">

        <label>E-mail</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" required minlength="6">

        <label>Confirmer le mot de passe</label>
        <input type="password" name="mot_de_passe2" required minlength="6">

        <button type="submit">S'inscrire</button>
    </form>

    <p class="lien">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

</body>
</html>
