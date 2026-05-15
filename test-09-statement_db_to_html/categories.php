<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db/connect.php';

$db = Connexion::getInstance();
$rows = $db->selectQuery("SELECT pk_categorie, nom, couleur FROM t_categorie ORDER BY nom");
echo json_encode($rows);
