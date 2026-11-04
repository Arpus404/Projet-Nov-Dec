
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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        header {
            background-color: #1e293b;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 23px;
            font-weight: 600;
        }

        header p {
            margin-top: 6px;
            color: #cbd5e1;
            font-size: 13px;
        }

        .user-info {
            font-size: 14px;
        }

        .user-info a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
            background: #334155;
            padding: 9px 13px;
            border-radius: 5px;
        }

        .user-info a:hover {
            background: #475569;
        }

        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h2 {
            font-size: 19px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .stat-card p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .stat-card strong {
            font-size: 27px;
            color: #1e293b;
        }

        .sensors {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .sensor-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
        }

        .sensor-card h3 {
            font-size: 16px;
            margin-bottom: 7px;
        }

        .sensor-id {
            color: #64748b;
            font-size: 12px;
        }

        .sensor-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status {
            padding: 5px 9px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .normal {
            color: #15803d;
            background: #dcfce7;
        }

        .warning {
            color: #b45309;
            background: #fef3c7;
        }

        .offline {
            color: #b91c1c;
            background: #fee2e2;
        }

        .sensor-info {
            color: #64748b;
            font-size: 13px;
            line-height: 1.8;
        }

        .measure-value {
            margin-top: 15px;
            padding: 13px;
            background: #f8fafc;
            border-radius: 5px;
        }

        .measure-value small {
            color: #64748b;
        }

        .measure-value strong {
            display: block;
            margin-top: 7px;
            font-size: 24px;
            color: #2563eb;
        }

        .table-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        button {
            padding: 9px 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            text-align: left;
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }

        .alarms {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
        }

        .alarm-item {
            padding: 12px;
            margin-top: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            border-radius: 4px;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
            font-size: 12px;
        }

        @media(max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .sensors {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 600px) {
            header {
                padding: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats, .sensors {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>

<body>

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

let sensors = [
    {
        id: 1,
        name: "Capteur Température",
        location: "Salle serveur",
        status: "normal",
        value: 22.5,
        unit: "°C",
        date: "2026-11-04 09:30:00",
        measures: [
            {id: 101, date: "2026-11-04 09:30:00", value: 22.5, unit: "°C"},
            {id: 102, date: "2026-11-04 09:00:00", value: 21.8, unit: "°C"}
        ]
    },
    {
        id: 2,
        name: "Capteur Humidité",
        location: "Laboratoire",
        status: "normal",
        value: 48,
        unit: "%",
        date: "2026-11-04 09:25:00",
        measures: [
            {id: 201, date: "2026-11-04 09:25:00", value: 48, unit: "%"},
            {id: 202, date: "2026-11-04 08:55:00", value: 51, unit: "%"}
        ]
    },
    {
        id: 3,
        name: "Capteur Pression",
        location: "Atelier",
        status: "warning",
        value: 105,
        unit: "hPa",
        date: "2026-11-04 09:20:00",
        measures: [
            {id: 301, date: "2026-11-04 09:20:00", value: 105, unit: "hPa"},
            {id: 302, date: "2026-11-04 08:50:00", value: 102, unit: "hPa"}
        ]
    }
];

function displaySensors() {

    const container = document.getElementById("sensorList");
    container.innerHTML = "";

    sensors.forEach(sensor => {

        let statusText = sensor.status === "normal" ? "NORMAL" : "ALERTE";

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

            let status = sensor.status === "warning" ? "warning" : "normal";
            let text = sensor.status === "warning" ? "ALERTE" : "NORMAL";

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

    let warnings = sensors.filter(s => s.status === "warning");

    if (warnings.length === 0) {
        alarmList.innerHTML = "<p>Aucune alarme active.</p>";
        return;
    }

    warnings.forEach(sensor => {
        alarmList.innerHTML += `
            <div class="alarm-item">
                ${sensor.name} : ${sensor.value} ${sensor.unit}
                (${sensor.location})
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
        sensors.filter(s => s.status === "warning").length;
}

function refreshData() {
    displaySensors();
    displayMeasures();
    displayAlarms();
}

displaySensors();
displayMeasures();
displayAlarms();

</script>

</body>
</html>
