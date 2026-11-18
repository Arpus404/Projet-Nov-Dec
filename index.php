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
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<header class="topbar">
    <div class="container topbar-inner">
        <h1>Supervision des capteurs</h1>
        <div class="user">
            <?= htmlspecialchars($_SESSION["user"]) ?>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
</header>

<main class="container">

    <section class="panel acquisition">
        <div class="acq-state">
            <span class="dot" id="acqPastille"></span>
            <span class="label">Reception</span>
            <span id="acqTexte" class="muted">chargement...</span>
        </div>
        <button id="acqBouton" class="btn btn-pause" disabled>Suspendre la mesure</button>
    </section>

    <div class="panel summary">
        <div class="kpi"><span>Capteurs</span><strong id="total">0</strong></div>
        <div class="kpi"><span>Normaux</span><strong id="normalCount">0</strong></div>
        <div class="kpi"><span>Alertes</span><strong id="warningCount">0</strong></div>
        <div class="kpi"><span>Alarmes</span><strong id="alarmCount">0</strong></div>
    </div>

    <section class="panel">
        <h2>Capteurs</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom</th>
                        <th>Emplacement</th>
                        <th>Dernière mesure</th>
                        <th>Valeur</th>
                        <th>État</th>
                    </tr>
                </thead>
                <tbody id="sensorTable"></tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Historique</h2>
            <button id="btnActualiser" class="btn">Actualiser</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N° mesure</th>
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
    </section>

    <section class="panel">
        <h2>Alarmes</h2>
        <div id="alarmList"></div>
    </section>

</main>

<script src="js/dashboard.js"></script>
</body>
</html>
