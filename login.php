
<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");

if (isset($_POST["login"])) {
    $nom = $_POST["nom"];
    $mdp = $_POST["mdp"];

    $sql = $pdo->prepare("SELECT * FROM User WHERE nom_User = ?");
    $sql->execute([$nom]);

    $user = $sql->fetch(PDO::FETCH_ASSOC);
        
    if ($user && password_verify($mdp, $user["mdp_User"])) {
        $_SESSION["user"] = $user["nom_User"];
        echo "Connexion réussie ! Bienvenue " . htmlspecialchars($user["nom_User"]);
    } else {
        echo "Nom ou mot de passe incorrect.";
    }
}
?>

<h2>Connexion</h2>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="password" name="mdp" placeholder="Mot de passe" required>
    <button type="submit" name="login">Se connecter</button>
</form>

<a href="register.php">Créer un compte</a>
