<?php
session_start();

// Si déjà connecté, redirige vers la page protégée
if (isset($_SESSION['utilisateur'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $email = trim($_POST['email']     ?? '');
    $mdp   = $_POST['mot_de_passe']  ?? '';

    if (!$email || !$mdp) {
        $erreur = "Tous les champs sont obligatoires.";

    } else {
        // Cherche l'utilisateur par e-mail
        $stmt = $pdo->prepare("SELECT * FROM t_utilisateur WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // password_verify() compare le mot de passe saisi avec le hash stocké en DB
        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            // Message volontairement vague pour ne pas indiquer si c'est l'email ou le mdp qui est faux
            $erreur = "E-mail ou mot de passe incorrect.";

        } else {
            // Connexion réussie : on stocke les infos en session (jamais le mot de passe !)
            $_SESSION['utilisateur'] = [
                'id'  => $user['pk_utilisateur'],
                'nom' => $user['nom_utilisateur'],
            ];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion — Test 10</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="carte">
    <h1>Se connecter</h1>

    <?php if ($erreur): ?>
        <div class="alerte erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>E-mail</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" required>

        <button type="submit">Se connecter</button>
    </form>

    <p class="lien">Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
</div>

</body>
</html>
