<?php
session_start();

// Déjà connecté : on va directement sur l'index
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");

$message = "";

if (isset($_POST["login"])) {
    $nom = $_POST["nom"];
    $mdp = $_POST["mdp"];

    $sql = $pdo->prepare("SELECT * FROM User WHERE nom_User = ?");
    $sql->execute([$nom]);

    $user = $sql->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user["mdp_User"])) {
        $_SESSION["user"] = $user["nom_User"];
        header("Location: index.php");
        exit;
    } else {
        $message = "Nom ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    
    <link rel="stylesheet" href="css/style.css">
    
</head>

<body class="page-login">

<div class="login">

    <h2>CONNEXION</h2>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="nom" placeholder="Nom" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <button type="submit" name="login">Se connecter</button>
    </form>

    <a href="register.php">Créer un compte</a>
</div>
<script>
    
const loginPage = document.querySelector(".page-login");

if (loginPage && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {

    loginPage.addEventListener("pointermove", (event) => {

        const x = (event.clientX / window.innerWidth) * 2 - 1;
        const y = (event.clientY / window.innerHeight) * 2 - 1;

        loginPage.style.setProperty("--rotate-x", `${-y * 2}deg`);
        loginPage.style.setProperty("--rotate-y", `${x * 2}deg`);

        loginPage.style.setProperty("--move-x", `${-x * 15}px`);
        loginPage.style.setProperty("--move-y", `${-y * 10}px`);
    });

    loginPage.addEventListener("pointerleave", () => {
        loginPage.style.setProperty("--rotate-x", "0deg");
        loginPage.style.setProperty("--rotate-y", "0deg");
        loginPage.style.setProperty("--move-x", "0px");
        loginPage.style.setProperty("--move-y", "0px");
    });
}
</script>

</body>
</html>

