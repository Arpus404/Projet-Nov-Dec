
<?php
$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");

$message = "";
$erreur = false;

if (isset($_POST["register"])) {
    $nom = trim($_POST["nom"]);

    // Le nom est-il déjà pris ? (Admin et admin comptent comme le même nom)
    $sql = $pdo->prepare("SELECT COUNT(*) FROM User WHERE nom_User = ?");
    $sql->execute([$nom]);

    if ($sql->fetchColumn() > 0) {
        $message = "Ce nom d'utilisateur est déjà pris, choisis-en un autre.";
        $erreur = true;
    } else {
        $mdp = password_hash($_POST["mdp"], PASSWORD_DEFAULT);

        try {
            $sql = $pdo->prepare("INSERT INTO User (nom_User, mdp_User, autor_User) VALUES (?, ?, ?)");
            $sql->execute([$nom, $mdp, "user"]);
            $message = "Utilisateur créé avec succès !";
        } catch (PDOException $e) {
            // Sécurité : la base refuse aussi les doublons (index UNIQUE sur nom_User)
            $message = "Ce nom d'utilisateur est déjà pris, choisis-en un autre.";
            $erreur = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-login">

<div class="register">

    <h2>INSCRIPTION</h2>

    <?php if ($message): ?>
        <p class="message<?= $erreur ? " erreur" : "" ?>"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="nom" placeholder="Nom" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <input type="text" name="mail" placeholder="Mail" required>

        <button type="submit" name="register">S'inscrire</button>
    </form>

    <a href="login.php">Se connecter</a>
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
