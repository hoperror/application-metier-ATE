<?php
require_once __DIR__ . "/config.php";

// Requête : vols + pilote + avion/modèle + essais + test + technicien
$sql = "
SELECT
  v.id AS idvol,
  v.depart,
  v.arrivee,
  v.horaire,

  p.id AS idpilote,
  p.nom AS pilote_nom,

  av.matricule AS avion_matricule,
  m.id AS modele_id,

  e.id AS idessai,
  e.dateessai,
  e.note,

  t.id AS idtest,
  t.nom AS test_nom,
  t.seuil AS test_seuil,

  tech.id AS idtechnicien,
  tech.nom AS technicien_nom

FROM vol v
LEFT JOIN pilote p     ON p.id = v.idpilote
LEFT JOIN avion av     ON av.matricule = v.idavion
LEFT JOIN modele m     ON m.id = av.idmodele

-- essais liés à l'avion
LEFT JOIN essai e      ON e.idavion = av.matricule
LEFT JOIN test t       ON t.id = e.idtest
LEFT JOIN technicien tech ON tech.id = e.idtechnicien

ORDER BY v.id, e.dateessai DESC NULLS LAST;
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Petite fonction : statut essai vs seuil (OK/KO/—)
function essaiStatus($note, $seuil) {
  if ($note === null || $seuil === null) return "—";
  return ((int)$note >= (int)$seuil) ? "OK" : "KO";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Vue intégrale — Vols / Pilotes / Essais</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 16px; }
    header { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    a { text-decoration:none; border:1px solid #ddd; padding:8px 10px; border-radius:8px; color:#333; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; vertical-align: top; }
    th { background: #fafafa; position: sticky; top: 0; z-index: 1; }
    .tag { padding: 2px 8px; border-radius: 999px; font-size: 12px; border: 1px solid #ddd; display:inline-block; }
    .ok { color: green; border-color: green; }
    .ko { color: red; border-color: red; }
    .muted { color:#666; font-size: 12px; }
  </style>
</head>
<body>

<header>
  <strong>Vue intégrale</strong>
  <span class="muted">Tous les vols + pilote + essais techniques + technicien</span>
  <span style="flex:1"></span>
  <a href="map.php">← Retour carte</a>
</header>

<p class="muted">
  Note : un vol peut apparaître plusieurs fois s’il y a plusieurs essais sur son avion.
</p>

<table>
  <thead>
    <tr>
      <th>Vol</th>
      <th>Départ</th>
      <th>Arrivée</th>
      <th>Horaire</th>
      <th>Pilote</th>
      <th>Avion / Modèle</th>
      <th>Essai</th>
      <th>Test</th>
      <th>Technicien</th>
      <th>Résultat</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <?php
        $status = essaiStatus($r["note"], $r["test_seuil"]);
        $cls = ($status === "OK") ? "tag ok" : (($status === "KO") ? "tag ko" : "tag");
      ?>
      <tr>
        <td>#<?= (int)$r["idvol"] ?></td>
        <td><?= htmlspecialchars($r["depart"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["arrivee"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["horaire"] ?? "") ?></td>

        <td>
          <?= htmlspecialchars($r["pilote_nom"] ?? "—") ?>
          <?php if (!empty($r["idpilote"])): ?>
            <div class="muted">id: <?= (int)$r["idpilote"] ?></div>
          <?php endif; ?>
        </td>

        <td>
          <?= htmlspecialchars($r["avion_matricule"] ?? "—") ?>
          <div class="muted">modèle: <?= htmlspecialchars($r["modele_id"] ?? "—") ?></div>
        </td>

        <td>
          <?= $r["idessai"] ? ("Essai #" . (int)$r["idessai"]) : "—" ?>
          <div class="muted"><?= htmlspecialchars($r["dateessai"] ?? "") ?></div>
          <div class="muted">note: <?= htmlspecialchars($r["note"] ?? "—") ?></div>
        </td>

        <td>
          <?= htmlspecialchars($r["test_nom"] ?? "—") ?>
          <div class="muted">seuil: <?= htmlspecialchars($r["test_seuil"] ?? "—") ?></div>
        </td>

        <td>
          <?= htmlspecialchars($r["technicien_nom"] ?? "—") ?>
          <?php if (!empty($r["idtechnicien"])): ?>
            <div class="muted">id: <?= (int)$r["idtechnicien"] ?></div>
          <?php endif; ?>
        </td>

        <td>
          <span class="<?= $cls ?>"><?= $status ?></span>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
