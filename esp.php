<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Ports COM (Sécurisé) - WAMP</title>
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-esp">

    <h1>Ports COM détectés (Méthode Sécurisée)</h1>
    <p>Cette méthode teste uniquement la connectivité sans exécuter de commandes système sur votre PC.</p>
    
    <a href="liste_ports.php" class="btn-refresh">Rafraîchir la page</a>

    <?php
    $ports_trouves = [];

    // On teste les ports de COM1 à COM9 (les numéros les plus courants pour l'ESP32)
    for ($i = 1; $i <= 9; $i++) {
        $nom_port = "COM" . $i;
        
        // Le symbole '@' permet de masquer l'erreur PHP si le port n'existe pas
        // On essaie d'ouvrir le port en mode lecture/écriture
        $fp = @fopen($nom_port, "r+");
        
        if ($fp !== false) {
            // Si l'ouverture réussit, le port existe et est libre !
            $ports_trouves[] = $nom_port;
            fclose($fp); // On referme immédiatement pour ne pas le bloquer
        } else {
            // Cas particulier sous Windows : si le port existe mais qu'un autre logiciel 
            // (comme Thonny) l'utilise déjà, PHP renvoie une erreur "Permission denied".
            // Cela prouve quand même que le port existe physiquement !
            $derniere_erreur = error_get_last();
            if ($derniere_erreur && strpos($derniere_erreur['message'], 'Permission denied') !== false) {
                $ports_trouves[] = $nom_port . " (Occupé par Thonny ou un autre logiciel)";
            }
        }
    }

    // Affichage du résultat
    if (!empty($ports_trouves)) {
        foreach ($ports_trouves as $port) {
            echo '<div class="port-box"><strong>Port actif trouvé :</strong> ' . htmlspecialchars($port) . '</div>';
        }
    } else {
        echo '<div class="no-port">Aucun port COM libre détecté entre COM1 et COM9. Vérifiez le branchement ou fermez Thonny.</div>';
    }
    ?>

</body>
</html>
