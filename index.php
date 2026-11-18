
<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervision des capteurs</title>

    
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-dashboard">

<header>
    <div>
        <h1>Supervision des capteurs</h1>
        <p>Suivi et surveillance des mesures</p>
    </div>

    <div class="user-info">
        Connecté : <?= htmlspecialchars($_SESSION["user"]) ?>
        <a href="logout.php">Déconnexion</a>
    </div>
</header>

<div class="container">

    <div class="acquisition">
        <div>
            <span class="pastille" id="acqPastille"></span>
            <strong>Acquisition :</strong> <span id="acqTexte">chargement...</span>
        </div>
        <button id="acqBouton" class="btn-pause" onclick="basculerAcquisition()" disabled>Suspendre la mesure</button>
    </div>

    <h2>Vue générale</h2>

    <div class="stats">
        <div class="stat-card">
            <p>Nombre de capteurs</p>
            <strong id="total">3</strong>
        </div>

        <div class="stat-card">
            <p>Capteurs normaux</p>
            <strong id="normalCount">2</strong>
        </div>

        <div class="stat-card">
            <p>Alertes</p>
            <strong id="warningCount">1</strong>
        </div>

        <div class="stat-card">
            <p>Alarmes actives</p>
            <strong id="alarmCount">1</strong>
        </div>
    </div>

    <h2>Liste des capteurs</h2>

    <div class="sensors" id="sensorList"></div>

    <div class="table-container">

        <div class="table-header">
            <h2>Historique des mesures</h2>
            <button onclick="refreshData()">Actualiser</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>N° Mesure</th>
                    <th>Capteur</th>
                    <th>Date</th>
                    <th>Valeur</th>
                    <th>Unité</th>
                    <th>État</th>
                </tr>
            </thead>

            <tbody id="measureTable"></tbody>
        </table>

    </div>

    <div class="alarms">
        <h2>Alarmes et notifications</h2>
        <div id="alarmList"></div>
    </div>

</div>

<footer>
    Application de supervision des capteurs - Projet étudiant
</footer>

<script>

// Données chargées depuis api.php (remplies par ecoute_serie.php)
let sensors = [];

const statusLabels = {normal: "NORMAL", warning: "ALERTE", offline: "HORS LIGNE"};

function displaySensors() {

    const container = document.getElementById("sensorList");
    container.innerHTML = "";

    if (sensors.length === 0) {
        container.innerHTML = "<p>Aucun capteur pour le moment. Lance lancer_ecoute.bat.</p>";
    }

    sensors.forEach(sensor => {

        let statusText = statusLabels[sensor.status];

        container.innerHTML += `
            <div class="sensor-card">
                <div class="sensor-top">
                    <div>
                        <h3>${sensor.name}</h3>
                        <p class="sensor-id">N° Capteur : ${sensor.id}</p>
                    </div>
                    <span class="status ${sensor.status}">${statusText}</span>
                </div>

                <div class="sensor-info">
                    Emplacement : ${sensor.location}<br>
                    Dernière mesure : ${sensor.date}
                </div>

                <div class="measure-value">
                    <small>Dernière valeur mesurée</small>
                    <strong>${sensor.value} ${sensor.unit}</strong>
                </div>
            </div>
        `;
    });

    updateStats();
}

function displayMeasures() {

    const table = document.getElementById("measureTable");
    table.innerHTML = "";

    sensors.forEach(sensor => {
        sensor.measures.forEach(measure => {

            let status = sensor.status;
            let text = statusLabels[sensor.status];

            table.innerHTML += `
                <tr>
                    <td>${measure.id}</td>
                    <td>${sensor.name}</td>
                    <td>${measure.date}</td>
                    <td>${measure.value}</td>
                    <td>${measure.unit}</td>
                    <td><span class="status ${status}">${text}</span></td>
                </tr>
            `;
        });
    });
}

function displayAlarms() {

    const alarmList = document.getElementById("alarmList");
    alarmList.innerHTML = "";

    let warnings = sensors.filter(s => s.status !== "normal");

    if (warnings.length === 0) {
        alarmList.innerHTML = "<p>Aucune alarme active.</p>";
        return;
    }

    warnings.forEach(sensor => {
        alarmList.innerHTML += `
            <div class="alarm-item">
                ${sensor.name} (${sensor.location}) : ${statusLabels[sensor.status]}
                - dernière valeur ${sensor.value} ${sensor.unit}
            </div>
        `;
    });
}

function updateStats() {

    document.getElementById("total").textContent = sensors.length;

    document.getElementById("normalCount").textContent =
        sensors.filter(s => s.status === "normal").length;

    document.getElementById("warningCount").textContent =
        sensors.filter(s => s.status === "warning").length;

    document.getElementById("alarmCount").textContent =
        sensors.filter(s => s.status !== "normal").length;
}

// ----- Suspendre / reprendre la mesure (libère le port pour Thonny) -----

let acquisition = null;

const acqTextes = {
    ecoute: "En écoute",
    recherche: "Recherche de l'ESP...",
    pause: "En pause : le port est libéré, tu peux utiliser Thonny",
    arrete: "Script d'écoute arrêté (lance lancer_ecoute.bat)"
};

function afficherAcquisition(e) {
    acquisition = e;
    let texte = acqTextes[e.etat] + (e.etat === "ecoute" && e.port ? " sur " + e.port : "");

    if (e.script_actif && e.pause_demandee && e.etat !== "pause") {
        texte = "Mise en pause...";
    } else if (e.script_actif && !e.pause_demandee && e.etat === "pause") {
        texte = "Reprise...";
    }

    document.getElementById("acqTexte").textContent = texte;
    document.getElementById("acqPastille").className = "pastille " + e.etat;

    const bouton = document.getElementById("acqBouton");
    bouton.textContent = e.pause_demandee ? "Reprendre la mesure" : "Suspendre la mesure";
    bouton.className = e.pause_demandee ? "btn-reprendre" : "btn-pause";
    bouton.disabled = !e.script_actif && !e.pause_demandee;
}

async function chargerAcquisition(action) {
    const options = action ? {method: "POST", body: new URLSearchParams({action: action})} : {};
    try {
        const response = await fetch("controle.php", options);
        if (response.ok) {
            afficherAcquisition(await response.json());
        }
    } catch (e) {
        console.error("Erreur controle.php", e);
    }
}

async function basculerAcquisition() {
    await chargerAcquisition(acquisition && acquisition.pause_demandee ? "reprendre" : "pause");
    setTimeout(chargerAcquisition, 1500); // le script met moins d'une seconde à réagir
}

async function refreshData() {
    chargerAcquisition();
    try {
        const response = await fetch("api.php");
        if (response.status === 401) {
            window.location = "login.php";
            return;
        }
        sensors = await response.json();
    } catch (e) {
        console.error("Erreur de chargement des données", e);
    }
    displaySensors();
    displayMeasures();
    displayAlarms();
}

refreshData();
setInterval(refreshData, 5000); // actualisation automatique toutes les 5 s

</script>

</body>
</html>
