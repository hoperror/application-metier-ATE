<?php
require_once __DIR__ . "/config.php";
header("Content-Type: application/json; charset=UTF-8");

// --- Récupération filtres GET ---
$statut = isset($_GET["statut"]) ? trim($_GET["statut"]) : "";
$aero   = isset($_GET["aero"])   ? strtoupper(trim($_GET["aero"])) : "";
$agent  = isset($_GET["agent"])  ? (int)$_GET["agent"] : 0;

$allowedStatus = ["", "OK", "KO", "EN_ATTENTE"];
if (!in_array($statut, $allowedStatus, true)) {
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "Statut invalide"]);
    exit;
}

$sql = "
SELECT
  v.id AS idvol,
  v.horaire AS horaire_vol,

  a_dep.code AS code_depart,
  a_dep.nom_aeroport AS aeroport_depart,
  a_dep.latitude AS lat,
  a_dep.longitude AS lon,

  c.date_check,
  c.statut,
  c.commentaire,

  ag.id AS idagent,
  ag.nom AS agent_nom,
  ag.email AS agent_email

FROM vol v

LEFT JOIN (
  SELECT DISTINCT ON (idvol)
    idvol, idagent, date_check, statut, commentaire
  FROM check_vol
  ORDER BY idvol, date_check DESC
) c ON c.idvol = v.id

LEFT JOIN agent_trafic ag ON ag.id = c.idagent
JOIN aeroports a_dep ON a_dep.code = v.depart

WHERE c.idvol IS NOT NULL
";

$params = [];

// Filtre statut
if ($statut !== "") {
  $sql .= " AND c.statut = :statut";
  $params[":statut"] = $statut;
}

// Filtre aéroport départ (code)
if ($aero !== "") {
  $sql .= " AND a_dep.code = :aero";
  $params[":aero"] = $aero;
}

// Filtre agent (id)
if ($agent > 0) {
  $sql .= " AND ag.id = :agent";
  $params[":agent"] = $agent;
}

$sql .= " ORDER BY v.id;";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  $data = [];
  foreach ($rows as $r) {
    $data[] = [
      "idvol" => (int)$r["idvol"],
      "horaire_vol" => $r["horaire_vol"],
      "code_depart" => $r["code_depart"],
      "aeroport_depart" => $r["aeroport_depart"],
      "lat" => (float)$r["lat"],
      "lon" => (float)$r["lon"],
      "date_check" => $r["date_check"],
      "statut" => $r["statut"],
      "commentaire" => $r["commentaire"],
      "idagent" => (int)$r["idagent"],
      "agent_nom" => $r["agent_nom"],
      "agent_email" => $r["agent_email"],
    ];
  }

  echo json_encode(["count" => count($data), "data" => $data],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()]);
}
