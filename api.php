<?php
// Indique au navigateur ou au client Web que le contenu renvoyé est du JSON
header('Content-Type: application/json; charset=utf-8');

// Chemin vers le fichier temporaire créé par le script d'écoute
$fichier = 'data.json';

if (file_exists($fichier)) {
    // Lit et affiche directement le contenu JSON stocké
    echo file_get_contents($fichier);
} else {
    // Message d'erreur si l'ESP32 n'a pas encore envoyé de données
    echo json_encode([
        "statut" => "error",
        "message" => "Aucune donnee recue pour le moment. Verifiez que le script d'ecoute tourne."
    ]);
}
?>
