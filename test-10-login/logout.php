<?php
session_start();

// Détruit toutes les données de session → l'utilisateur est déconnecté
session_destroy();

header('Location: login.php');
exit;
