<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/db/connect.php';

    $db = Connexion::getInstance();

    $nom          = $_POST['nom']          ?? null;
    $description  = $_POST['description']  ?? null;
    $type         = $_POST['type']         ?? null; // 'point' ou 'polygone'
    $fk_categorie = $_POST['fk_categorie'] ?? null;
    $date         = $_POST['date_enregistrement']  ?? date('Y-m-d');
    $heure        = $_POST['heure_enregistrement'] ?? date('H:i:s');

    if (!$nom || !$type || !$fk_categorie) {
        echo json_encode(['success' => false, 'error' => 'Champs obligatoires manquants']);
        exit;
    }

    $db->beginTransaction();

    // 1. Insertion du relevé
    if ($type === 'point') {
        $lat = $_POST['latitude']  ?? null;
        $lng = $_POST['longitude'] ?? null;

        $db->ExecuteQuery("
            INSERT INTO t_releve (nom, description, type, latitude, longitude, date_enregistrement, heure_enregistrement, fk_categorie)
            VALUES (:nom, :desc, 'point', :lat, :lng, :d, :h, :cat)
        ", [
            ':nom'  => $nom,
            ':desc' => $description,
            ':lat'  => $lat,
            ':lng'  => $lng,
            ':d'    => $date,
            ':h'    => $heure,
            ':cat'  => $fk_categorie
        ]);
    } elseif ($type === 'polygone') {
        $db->ExecuteQuery("
            INSERT INTO t_releve (nom, description, type, date_enregistrement, heure_enregistrement, fk_categorie)
            VALUES (:nom, :desc, 'polygone', :d, :h, :cat)
        ", [
            ':nom'  => $nom,
            ':desc' => $description,
            ':d'    => $date,
            ':h'    => $heure,
            ':cat'  => $fk_categorie
        ]);
    }

    $pk_releve = (int)$db->lastInsertId();

    // 2. Points du polygone
    if ($type === 'polygone' && !empty($_POST['points'])) {
        $stmtPt = $db->prepare("
            INSERT INTO t_point_polygone (ordre, latitude, longitude, fk_releve)
            VALUES (:ordre, :lat, :lng, :fk)
        ");
        foreach ($_POST['points'] as $i => $p) {
            $stmtPt->execute([
                ':ordre' => $i,
                ':lat'   => $p['latitude'],
                ':lng'   => $p['longitude'],
                ':fk'    => $pk_releve
            ]);
        }
    }

    // 3. Images (multiples) — on collecte les IDs pour les renvoyer au JS
    $photoIds = [];
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $stmtImg = $db->prepare("
            INSERT INTO t_photo (nom_fichier, type_mime, contenu, fk_releve)
            VALUES (:nom, :type, :contenu, :fk)
        ");
        $files = $_FILES['images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== 0) continue;
            $contenu = file_get_contents($files['tmp_name'][$i]);
            $stmtImg->bindValue(':nom',     $files['name'][$i]);
            $stmtImg->bindValue(':type',    $files['type'][$i]);
            $stmtImg->bindValue(':contenu', $contenu, PDO::PARAM_LOB);
            $stmtImg->bindValue(':fk',      $pk_releve, PDO::PARAM_INT);
            $stmtImg->execute();
            $photoIds[] = (int)$db->lastInsertId();
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'id' => $pk_releve, 'photos' => $photoIds]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
