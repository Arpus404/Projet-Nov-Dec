<?php
// Script d'écoute du port série : lit ce que l'ESP envoie et l'enregistre en base.
// A lancer en console (pas dans le navigateur) : lancer_ecoute.bat
// Utilisation : php ecoute_serie.php        -> détection automatique de l'ESP
//               php ecoute_serie.php COM3   -> port imposé

if (PHP_SAPI !== "cli") {
    exit("Ce script se lance en console : double-clique sur lancer_ecoute.bat");
}

date_default_timezone_set("Europe/Paris");

$baud = 115200;
$portImpose = isset($argv[1]) ? strtoupper($argv[1]) : null;
$delaiSilence = 30; // sans données pendant 30 s, on considère l'ESP perdu

$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ---------- Pause (bouton "Suspendre la mesure" de la page web) ----------
// La page crée pause.flag pour suspendre, le supprime pour reprendre.
// Le script écrit son état dans etat_ecoute.json pour que la page l'affiche.

define("FICHIER_PAUSE", __DIR__ . "/pause.flag");
define("FICHIER_ETAT", __DIR__ . "/etat_ecoute.json");

// Au lancement du script, on repart toujours en mode mesure
@unlink(FICHIER_PAUSE);

function enPause() {
    static $dernierTest = 0, $resultat = false;
    if (microtime(true) - $dernierTest >= 0.5) { // on vérifie 2 fois par seconde
        // On liste le dossier plutôt que file_exists() : sous Windows, file_exists()
        // renvoie parfois false quand l'antivirus analyse le fichier
        $fichiers = @scandir(__DIR__);
        if ($fichiers !== false) {
            $resultat = in_array(basename(FICHIER_PAUSE), $fichiers);
        }
        $dernierTest = microtime(true);
    }
    return $resultat;
}

// Etat : "ecoute", "recherche" ou "pause" (réécrit toutes les 5 s pour montrer que le script tourne)
function ecrireEtat($etat, $port = null) {
    static $dernier = null, $derniereEcriture = 0;
    if ("$etat|$port" === $dernier && time() - $derniereEcriture < 5) {
        return;
    }
    @file_put_contents(FICHIER_ETAT, json_encode(["etat" => $etat, "port" => $port, "maj" => time()]));
    $dernier = "$etat|$port";
    $derniereEcriture = time();
}

function attendreFinPause() {
    echo "\nMESURE SUSPENDUE : le port est libéré, tu peux utiliser Thonny.\n";
    echo "Ferme Thonny puis clique sur \"Reprendre la mesure\" dans la page web.\n";
    while (enPause()) {
        ecrireEtat("pause");
        usleep(200000);
    }
    echo "Reprise de la mesure\n\n";
}

// Attend quelques secondes, en s'arrêtant tout de suite si on demande une pause
function attendre($secondes) {
    $fin = time() + $secondes;
    while (time() < $fin && !enPause()) {
        usleep(200000);
    }
}


// ---------- Port série ----------

// Liste des ports COM USB branchés (là où l'ESP peut être), sans COM1 & co
function listerPorts() {
    // Ports présents en ce moment
    exec('reg query HKLM\HARDWARE\DEVICEMAP\SERIALCOMM 2>nul', $presents);

    // Ports créés par un adaptateur USB (CH340, CP2102, FTDI...)
    exec('reg query HKLM\SYSTEM\CurrentControlSet\Enum\USB /s /v PortName 2>nul', $usb);
    exec('reg query HKLM\SYSTEM\CurrentControlSet\Enum\FTDIBUS /s /v PortName 2>nul', $usb);

    $portsUsb = [];
    foreach ($usb as $l) {
        if (preg_match('/REG_SZ\s+(COM\d+)/', $l, $m)) {
            $portsUsb[] = $m[1];
        }
    }

    $ports = [];
    foreach ($presents as $l) {
        if (preg_match('/REG_SZ\s+(COM\d+)/', $l, $m) && in_array($m[1], $portsUsb)) {
            $ports[] = $m[1];
        }
    }
    return $ports;
}

// Configure et ouvre un port, renvoie null s'il est occupé ou absent
function ouvrirPort($port, $baud) {
    // DTR/RTS coupés pour ne pas bloquer l'ESP en reset
    exec("mode $port BAUD=$baud PARITY=N DATA=8 STOP=1 DTR=OFF RTS=OFF to=off xon=off 2>nul", $sortie, $code);
    if ($code !== 0) {
        return null;
    }
    $fp = @fopen("\\\\.\\$port", "r+b");
    if (!$fp) {
        return null;
    }
    stream_set_read_buffer($fp, 0);
    return $fp;
}

// Lit les caractères disponibles : renvoie une ligne complète, null si rien, false si port perdu
function lireLigne($fp, &$tampon) {
    while (true) {
        $c = fread($fp, 1);
        if ($c === false) {
            return false;
        }
        if ($c === "") {
            return null;
        }
        if ($c === "\n") {
            $ligne = $tampon;
            $tampon = "";
            return $ligne;
        }
        if ($c !== "\r") {
            $tampon .= $c;
        }
    }
}

function estLigneEsp($ligne) {
    $data = json_decode(trim($ligne), true);
    return is_array($data) && isset($data["num_Capteur"]);
}

// Essaie chaque port jusqu'à recevoir une ligne JSON de l'ESP (null si pause demandée)
function detecterEsp($baud) {
    while (true) {
        if (enPause()) {
            return null;
        }
        ecrireEtat("recherche");
        $ports = listerPorts();
        echo "Recherche de l'ESP sur : " . ($ports ? implode(", ", $ports) : "aucun port") . "\n";

        foreach ($ports as $port) {
            $fp = ouvrirPort($port, $baud);
            if (!$fp) {
                echo "  $port : occupé (Thonny ?) ou indisponible\n";
                continue;
            }

            echo "  $port : écoute... ";
            $tampon = "";
            $fin = microtime(true) + 8; // l'ESP envoie une mesure toutes les 5 s
            while (microtime(true) < $fin) {
                if (enPause()) {
                    fclose($fp);
                    return null;
                }
                ecrireEtat("recherche");
                $ligne = lireLigne($fp, $tampon);
                if ($ligne === false) {
                    break;
                }
                if ($ligne === null) {
                    usleep(10000);
                    continue;
                }
                if (estLigneEsp($ligne)) {
                    echo "ESP trouvé !\n";
                    return [$port, $fp, $ligne];
                }
            }
            echo "pas d'ESP\n";
            fclose($fp);
        }

        echo "ESP introuvable, nouvel essai dans 5 s (branche l'ESP / ferme Thonny)\n\n";
        attendre(5);
    }
}


// ---------- Base de données ----------

// Crée le capteur en base s'il n'existe pas encore (avec le numéro envoyé par l'ESP)
function verifierCapteur($pdo, $num, $nom) {
    $sql = $pdo->prepare("SELECT COUNT(*) FROM capteurs WHERE num_Capteur = ?");
    $sql->execute([$num]);
    if (!$sql->fetchColumn()) {
        $pdo->prepare("INSERT INTO capteurs (num_Capteur, nom_Capteur, emplacement) VALUES (?, ?, ?)")
            ->execute([$num, $nom, ""]);
    }
}

// Retrouve la grandeur mesurée (Temperature, Pression...) ou la crée
function trouverMesurande($pdo, $lib, $unite) {
    $sql = $pdo->prepare("SELECT num_Mesure FROM mesurandes WHERE lib_Mesure = ?");
    $sql->execute([$lib]);
    $num = $sql->fetchColumn();
    if ($num) {
        return $num;
    }
    $pdo->prepare("INSERT INTO mesurandes (lib_Mesure, unite_Mesurandes) VALUES (?, ?)")->execute([$lib, $unite]);
    return $pdo->lastInsertId();
}

function traiterLigne($pdo, $ligne) {
    $ligne = trim($ligne);
    if ($ligne === "") {
        return;
    }

    // Les lignes qui ne sont pas du JSON (print de debug) sont juste affichées
    $data = json_decode($ligne, true);
    if (!is_array($data) || !isset($data["num_Capteur"], $data["mesures"])) {
        echo "[" . date("H:i:s") . "] $ligne\n";
        return;
    }

    // L'ESP n'a pas d'horloge : c'est le PC qui date la mesure
    $date = $pdo->query("SELECT NOW()")->fetchColumn();
    $data = ["num_Capteur" => $data["num_Capteur"], "nom_Capteur" => $data["nom_Capteur"] ?? "Capteur ESP",
             "gpio" => $data["gpio"] ?? null, "date_Mesure" => $date, "mesures" => $data["mesures"]];

    verifierCapteur($pdo, $data["num_Capteur"], $data["nom_Capteur"]);

    $insert = $pdo->prepare("INSERT INTO mesure (date_Mesure, val_Mesure, num_Capteur, num_Mesure) VALUES (?, ?, ?, ?)");
    foreach ($data["mesures"] as $m) {
        $numMesure = trouverMesurande($pdo, $m["lib_Mesure"], $m["unite_Mesurandes"] ?? "");
        $insert->execute([$date, $m["val_Mesure"], $data["num_Capteur"], $numMesure]);
    }

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "    -> enregistré en base\n";
}

function traiterSansPlanter($pdo, $ligne) {
    try {
        traiterLigne($pdo, $ligne);
    } catch (Exception $e) {
        echo "Erreur base de données : " . $e->getMessage() . "\n";
    }
}


// ---------- Boucle principale ----------

while (true) {
    if (enPause()) {
        attendreFinPause();
        continue;
    }

    if ($portImpose) {
        $port = $portImpose;
        $fp = ouvrirPort($port, $baud);
        $premiere = null;
        if (!$fp) {
            ecrireEtat("recherche", $port);
            echo "Impossible d'ouvrir $port (occupé par Thonny ou débranché), nouvel essai dans 5 s\n";
            attendre(5);
            continue;
        }
    } else {
        $trouve = detecterEsp($baud);
        if ($trouve === null) {
            continue; // pause demandée pendant la recherche
        }
        list($port, $fp, $premiere) = $trouve;
    }

    echo "\nÉcoute de $port à $baud bauds... (Ctrl+C pour arrêter)\n";
    if ($premiere !== null) {
        traiterSansPlanter($pdo, $premiere);
    }

    $tampon = "";
    $derniereDonnee = time();

    while (true) {
        if (enPause()) {
            break; // on ferme le port pour le laisser à Thonny
        }
        ecrireEtat("ecoute", $port);

        $ligne = lireLigne($fp, $tampon);

        if ($ligne === false || time() - $derniereDonnee > $delaiSilence) {
            echo "\nPlus de données sur $port (ESP débranché ?), nouvelle recherche...\n\n";
            break;
        }
        if ($ligne === null) {
            usleep(10000); // rien reçu, on attend un peu
            continue;
        }

        $derniereDonnee = time();
        traiterSansPlanter($pdo, $ligne);
    }

    fclose($fp);
}
