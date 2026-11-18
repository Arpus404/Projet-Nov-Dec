<?php
// Suspend / reprend la mesure et renvoie l'état du script d'écoute (utilisé par index.php)
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erreur" => "Non connecté"]);
    exit;
}

$fichierPause = __DIR__ . "/pause.flag";
$fichierEtat = __DIR__ . "/etat_ecoute.json";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "pause") {
        file_put_contents($fichierPause, date("Y-m-d H:i:s"));
    } elseif ($action === "reprendre" && file_exists($fichierPause)) {
        unlink($fichierPause);
    }
}

// Le script réécrit son état toutes les 5 s : plus vieux que 15 s = script arrêté
$etat = file_exists($fichierEtat) ? json_decode(file_get_contents($fichierEtat), true) : null;
$actif = $etat && time() - $etat["maj"] <= 15;

echo json_encode([
    "script_actif"   => $actif,
    "etat"           => $actif ? $etat["etat"] : "arrete",
    "port"           => $actif ? $etat["port"] : null,
    "pause_demandee" => file_exists($fichierPause)
]);
