<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO(
        "mysql:host=db;dbname=testdb;charset=utf8mb4",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // On récupère tous les relevés avec le nom et la couleur de leur catégorie
    $stmt = $pdo->query("
        SELECT
            r.pk_releve,
            r.nom,
            r.description,
            r.type,
            r.latitude,
            r.longitude,
            r.date_enregistrement,
            r.heure_enregistrement,
            c.nom        AS cat_nom,
            c.couleur    AS cat_couleur
        FROM t_releve r
        JOIN t_categorie c ON r.fk_categorie = c.pk_categorie
        ORDER BY r.date_enregistrement DESC, r.heure_enregistrement DESC
    ");
    $releves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque polygone, on récupère ses points triés par ordre
    $stmtPts = $pdo->prepare("
        SELECT latitude, longitude
        FROM t_point_polygone
        WHERE fk_releve = :id
        ORDER BY ordre ASC
    ");

    $stmtPhotos = $pdo->prepare("
        SELECT pk_photo FROM t_photo WHERE fk_releve = :id ORDER BY pk_photo ASC
    ");

    foreach ($releves as &$r) {
        if ($r['type'] === 'polygone') {
            $stmtPts->execute([':id' => $r['pk_releve']]);
            $r['points'] = $stmtPts->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $r['points'] = [];
        }

        // IDs des photos pour les afficher dans la popup via photo.php?id=X
        $stmtPhotos->execute([':id' => $r['pk_releve']]);
        $r['photos'] = array_column($stmtPhotos->fetchAll(PDO::FETCH_ASSOC), 'pk_photo');
    }

    echo json_encode($releves);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
