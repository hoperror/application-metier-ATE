<?php
require_once __DIR__ . "/config.php";

$message = "";
$error = "";

// 1) Charger les listes pour les <select>
$vols = $pdo->query("
  SELECT v.id, v.depart, v.arrivee, v.horaire
  FROM vol v
  ORDER BY v.horaire DESC
")->fetchAll();

$agents = $pdo->query("
  SELECT id, nom, email
  FROM agent_trafic
  ORDER BY nom
")->fetchAll();

// 2) Traitement formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idvol = isset($_POST["idvol"]) ? (int)$_POST["idvol"] : 0;
    $idagent = isset($_POST["idagent"]) ? (int)$_POST["idagent"] : 0;
    $statut = isset($_POST["statut"]) ? trim($_POST["statut"]) : "";
    $commentaire = isset($_POST["commentaire"]) ? trim($_POST["commentaire"]) : "";

    $allowed = ["OK", "KO", "EN_ATTENTE"];

    if ($idvol <= 0 || $idagent <= 0) {
        $error = "Vol et agent sont obligatoires.";
    } elseif (!in_array($statut, $allowed, true)) {
        $error = "Statut invalide.";
    } else {
        try {
            $sql = "INSERT INTO check_vol (idvol, idagent, statut, commentaire)
                    VALUES (:idvol, :idagent, :statut, :commentaire)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":idvol" => $idvol,
                ":idagent" => $idagent,
                ":statut" => $statut,
                ":commentaire" => ($commentaire === "" ? null : $commentaire)
            ]);

            // PRG pattern : redirige après POST (évite double insertion au refresh)
            header("Location: map.php?added=1");
            exit;

        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Ajouter un check vol</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 820px; }
    h1 { margin-top: 0; }
    form { border: 1px solid #ddd; padding: 16px; border-radius: 10px; }
    label { display:block; margin-top: 12px; font-weight: bold; }
    select, textarea, button {
      width: 100%; padding: 10px; margin-top: 6px;
      border-radius: 8px; border: 1px solid #ccc; font-size: 14px;
    }
    textarea { min-height: 90px; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .error { background: #ffe6e6; border: 1px solid #ff9b9b; padding: 10px; border-radius: 8px; }
    .hint { color:#666; margin-top: 8px; }
    .actions { display:flex; gap: 10px; margin-top: 14px; }
    .actions a { display:inline-block; padding:10px 12px; border:1px solid #ddd; border-radius:8px; text-decoration:none; color:#333; }
    button { cursor:pointer; border: none; background:#1f6feb; color:white; }
    button:hover { filter: brightness(0.95); }
  </style>
</head>
<body>

  <h1>Ajouter un check de vol</h1>
  <p class="hint">
    Ce formulaire insère une ligne dans <code>check_vol</code>. La carte (Leaflet) affichera ensuite le vol au départ avec la couleur du statut.
  </p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="row">
      <div>
        <label for="idvol">Vol</label>
        <select name="idvol" id="idvol" required>
          <option value="">-- Choisir un vol --</option>
          <?php foreach ($vols as $v): ?>
            <?php
              $label = "Vol #{$v['id']} — {$v['depart']} → {$v['arrivee']} — {$v['horaire']}";
            ?>
            <option value="<?= (int)$v["id"] ?>">
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="idagent">Agent</label>
        <select name="idagent" id="idagent" required>
          <option value="">-- Choisir un agent --</option>
          <?php foreach ($agents as $a): ?>
            <?php $label = "{$a['nom']} ({$a['email']})"; ?>
            <option value="<?= (int)$a["id"] ?>">
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <label for="statut">Statut</label>
    <select name="statut" id="statut" required>
      <option value="OK">OK (vert)</option>
      <option value="KO">KO (rouge)</option>
      <option value="EN_ATTENTE">EN_ATTENTE (orange)</option>
    </select>

    <label for="commentaire">Commentaire (optionnel)</label>
    <textarea name="commentaire" id="commentaire" placeholder="Ex: RAS / Document manquant / Problème de bagage..."></textarea>

    <div class="actions">
      <button type="submit">Enregistrer le check</button>
      <a href="map.php">Retour à la carte</a>
    </div>
  </form>

</body>
</html>
