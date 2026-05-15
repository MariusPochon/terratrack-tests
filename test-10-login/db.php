<?php
// Connexion PDO partagée — inclus dans les autres fichiers avec require_once
// PDO::ERRMODE_EXCEPTION = lance une exception si une requête échoue (plus facile à déboguer)

$pdo = new PDO(
    "mysql:host=db;dbname=testdb;charset=utf8mb4",
    "root",
    "root",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
