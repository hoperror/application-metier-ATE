<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Suivi checking vols — Carte + tableau</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  >

  <style>
    body { margin: 0; font-family: Arial, sans-serif; }
    header {
      padding: 12px 16px;
      border-bottom: 1px solid #ddd;
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    header strong { margin-right: 10px; }
    header .spacer { flex: 1; }
    header select {
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    header a {
      text-decoration: none;
      border: 1px solid #ddd;
      padding: 8px 10px;
      border-radius: 8px;
      color: #333;
    }
    #map { height: 58vh; }

    .legend {
      background: white;
      padding: 10px 12px;
      border-radius: 8px;
      box-shadow: 0 1px 8px rgba(0,0,0,0.2);
      font-size: 14px;
      line-height: 1.4;
    }
    .dot {
      display: inline-block;
      width: 10px; height: 10px;
      border-radius: 50%;
      margin-right: 8px;
    }

    main { padding: 14px 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; }
    th { background: #fafafa; }
    .tag {
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 12px;
      border: 1px solid #ddd;
      display: inline-block;
    }
  </style>
</head>

<body>
  <header>
    <strong>Suivi checking vols</strong>
    <br>
    <label>
      Statut :
      <select id="fStatut">
        <option value="">Tous</option>
        <option value="OK">OK</option>
        <option value="KO">KO</option>
        <option value="EN_ATTENTE">EN_ATTENTE</option>
      </select>
    </label>

    <label>
      Aéroport départ :
      <select id="fAero">
        <option value="">Tous</option>
      </select>
    </label>

    <label>
      Agent :
      <select id="fAgent">
        <option value="">Tous</option>
      </select>
    </label>

    <span class="spacer"></span>
    <a href="add_check.php">+ Nouveau check</a>

    <a href="vols_full.php">📋 Vue intégrale vols</a>
  </header>

  <div id="map"></div>

  <main>
    <h3 style="margin:10px 0;">Liste des vols checkés</h3>
    <div id="count" style="color:#666;margin-bottom:10px;"></div>
    <table>
      <thead>
        <tr>
          <th>Vol</th>
          <th>Départ</th>
          <th>Horaire</th>
          <th>Statut</th>
          <th>Agent</th>
          <th>Date check</th>
          <th>Commentaire</th>
        </tr>
      </thead>
      <tbody id="tbody"></tbody>
    </table>
  </main>

  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
  ></script>

  <script>
    const map = L.map('map').setView([46.6, 2.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    function colorByStatus(statut) {
      if (statut === "OK") return "green";
      if (statut === "KO") return "red";
      if (statut === "EN_ATTENTE") return "orange";
      return "gray";
    }

    // Légende
    const legend = L.control({ position: "bottomright" });
    legend.onAdd = function() {
      const div = L.DomUtil.create("div", "legend");
      div.innerHTML = `
        <div><span class="dot" style="background:green"></span>OK</div>
        <div><span class="dot" style="background:red"></span>KO</div>
        <div><span class="dot" style="background:orange"></span>EN_ATTENTE</div>
      `;
      return div;
    };
    legend.addTo(map);

    // Couche des markers (pour nettoyer/recharger)
    let layer = L.layerGroup().addTo(map);

    const fStatut = document.getElementById("fStatut");
    const fAero   = document.getElementById("fAero");
    const fAgent  = document.getElementById("fAgent");

    function buildUrl() {
      const params = new URLSearchParams();
      if (fStatut.value) params.set("statut", fStatut.value);
      if (fAero.value)   params.set("aero", fAero.value);
      if (fAgent.value)  params.set("agent", fAgent.value);
      const qs = params.toString();
      return "checks_depart.php" + (qs ? ("?" + qs) : "");
    }

    function populateFilters(data) {
      // On alimente les listes (sans dupliquer)
      const aeroSet = new Map();  // code -> nom
      const agentSet = new Map(); // id -> label

      data.forEach(it => {
        aeroSet.set(it.code_depart, it.aeroport_depart);
        agentSet.set(it.idagent, `${it.agent_nom} (${it.agent_email})`);
      });

      // Aéroports
      const currentAero = fAero.value;
      fAero.innerHTML = '<option value="">Tous</option>';
      [...aeroSet.entries()].sort().forEach(([code, nom]) => {
        const opt = document.createElement("option");
        opt.value = code;
        opt.textContent = `${code} — ${nom}`;
        fAero.appendChild(opt);
      });
      fAero.value = currentAero; // garde la sélection si possible

      // Agents
      const currentAgent = fAgent.value;
      fAgent.innerHTML = '<option value="">Tous</option>';
      [...agentSet.entries()].sort((a,b) => a[1].localeCompare(b[1])).forEach(([id, label]) => {
        const opt = document.createElement("option");
        opt.value = id;
        opt.textContent = label;
        fAgent.appendChild(opt);
      });
      fAgent.value = currentAgent;
    }

    function renderTable(data) {
      const tbody = document.getElementById("tbody");
      tbody.innerHTML = "";

      data.forEach(it => {
        const col = colorByStatus(it.statut);
        const tr = document.createElement("tr");

        tr.innerHTML = `
          <td>#${it.idvol}</td>
          <td>${it.code_depart} — ${it.aeroport_depart}</td>
          <td>${it.horaire_vol ?? ""}</td>
          <td><span class="tag" style="border-color:${col};color:${col}">${it.statut}</span></td>
          <td>${it.agent_nom ?? ""}<br><span style="color:#666;font-size:12px">${it.agent_email ?? ""}</span></td>
          <td>${it.date_check ?? ""}</td>
          <td>${it.commentaire ?? ""}</td>
        `;

        tbody.appendChild(tr);
      });

      document.getElementById("count").textContent =
        `${data.length} vol(s) checké(s) affiché(s) selon les filtres.`;
    }

    function renderMap(data) {
      layer.clearLayers();
      const bounds = [];

      data.forEach(it => {
        const lat = Number(it.lat);
        const lon = Number(it.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

        const col = colorByStatus(it.statut);

        const marker = L.circleMarker([lat, lon], {
          radius: 8,
          color: col,
          fillColor: col,
          fillOpacity: 0.85,
          weight: 2
        });

        const popupHtml = `
          <div style="min-width:240px">
            <div><strong>Vol #${it.idvol}</strong> — <span style="color:${col}">${it.statut}</span></div>
            <div>Départ : <strong>${it.code_depart}</strong> (${it.aeroport_depart})</div>
            <div>Horaire : ${it.horaire_vol ?? ""}</div>
            <hr style="border:0;border-top:1px solid #ddd;margin:8px 0">
            <div>Agent : <strong>${it.agent_nom ?? ""}</strong></div>
            <div>Email : ${it.agent_email ?? ""}</div>
            <div>Check : ${it.date_check ?? ""}</div>
            <div style="margin-top:6px;color:#444">
              ${it.commentaire ? ("💬 " + it.commentaire) : ""}
            </div>
          </div>
        `;

        marker.bindPopup(popupHtml);
        marker.addTo(layer);
        bounds.push([lat, lon]);
      });

      if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30] });
      }
    }

    function loadData() {
      const url = buildUrl();
      fetch(url)
        .then(r => r.json())
        .then(json => {
          const data = json.data || [];
          // Les filtres dropdown doivent proposer ce qui existe (sur l'ensemble)
          // Pour éviter qu'un filtre vide la liste, on recharge les choix à partir des données courantes.
          populateFilters(data);
          renderTable(data);
          renderMap(data);
        })
        .catch(err => {
          console.error(err);
          alert("Erreur chargement données.");
        });
    }

    // Événements filtres
    [fStatut, fAero, fAgent].forEach(sel => sel.addEventListener("change", loadData));

    // Chargement initial
    loadData();
  </script>
</body>
</html>
