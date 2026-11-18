<?php
// Renvoie les capteurs et leurs dernières mesures en JSON (utilisé par index.php)
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erreur" => "Non connecté"]);
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");

// Un capteur sans mesure depuis plus de 30 s est considéré hors ligne
$delaiHorsLigne = 30;

$capteurs = $pdo->query("SELECT * FROM capteurs ORDER BY num_Capteur")->fetchAll(PDO::FETCH_ASSOC);

$sqlMesures = $pdo->prepare("
    SELECT m.id_mesure, m.date_Mesure, m.val_Mesure, me.unite_Mesurandes,
           TIMESTAMPDIFF(SECOND, m.date_Mesure, NOW()) AS age
    FROM mesure m
    JOIN mesurandes me ON me.num_Mesure = m.num_Mesure
    WHERE m.num_Capteur = ?
    ORDER BY m.date_Mesure DESC, m.id_mesure DESC
    LIMIT 20
");

$resultat = [];

foreach ($capteurs as $c) {
    $sqlMesures->execute([$c["num_Capteur"]]);
    $mesures = $sqlMesures->fetchAll(PDO::FETCH_ASSOC);
    $derniere = $mesures[0] ?? null;

    // Dernières valeurs de la même date (ex : "22.5 °C / 1013.25 hPa")
    $valeurs = [];
    foreach ($mesures as $m) {
        if ($m["date_Mesure"] === $derniere["date_Mesure"]) {
            array_unshift($valeurs, (float) $m["val_Mesure"] . " " . $m["unite_Mesurandes"]);
        }
    }

    $resultat[] = [
        "id"       => (int) $c["num_Capteur"],
        "name"     => $c["nom_Capteur"],
        "location" => $c["emplacement"],
        "status"   => ($derniere && $derniere["age"] <= $delaiHorsLigne) ? "normal" : "offline",
        "value"    => $valeurs ? implode(" / ", $valeurs) : "-",
        "unit"     => "",
        "date"     => $derniere ? $derniere["date_Mesure"] : "aucune",
        "measures" => array_map(function ($m) {
            return [
                "id"    => (int) $m["id_mesure"],
                "date"  => $m["date_Mesure"],
                "value" => (float) $m["val_Mesure"],
                "unit"  => $m["unite_Mesurandes"]
            ];
        }, $mesures)
    ];
}

echo json_encode($resultat);
