<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/db/connect.php';

    $db = Connexion::getInstance();

    $releves = $db->selectQuery("
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

    $stmtPts    = $db->prepare("SELECT latitude, longitude FROM t_point_polygone WHERE fk_releve = :id ORDER BY ordre ASC");
    $stmtPhotos = $db->prepare("SELECT pk_photo FROM t_photo WHERE fk_releve = :id ORDER BY pk_photo ASC");

    foreach ($releves as &$r) {
        if ($r['type'] === 'polygone') {
            $stmtPts->execute([':id' => $r['pk_releve']]);
            $r['points'] = $stmtPts->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $r['points'] = [];
        }

        $stmtPhotos->execute([':id' => $r['pk_releve']]);
        $r['photos'] = array_column($stmtPhotos->fetchAll(PDO::FETCH_ASSOC), 'pk_photo');
    }

    echo json_encode($releves);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
