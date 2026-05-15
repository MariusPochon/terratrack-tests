<?php
header('Content-Type: application/json');

$pdo = new PDO(
    "mysql:host=db;dbname=testdb;charset=utf8mb4",
    "root",
    "root"
);

$stmt = $pdo->query("SELECT pk_categorie, nom, couleur FROM t_categorie ORDER BY nom");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
