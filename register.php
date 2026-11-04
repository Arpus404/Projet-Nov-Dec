
<?php
$pdo = new PDO("mysql:host=localhost;dbname=gestion_capteurs;charset=utf8mb4", "root", "");

if (isset($_POST["register"])) {
    $nom = $_POST["nom"];
    $mdp = password_hash($_POST["mdp"], PASSWORD_DEFAULT);

    $sql = $pdo->prepare("INSERT INTO User (nom_User, mdp_User, autor_User) VALUES (?, ?, ?)");
    $sql->execute([$nom, $mdp, "user"]);

    echo "Utilisateur créé avec succès !";
}
?>

<h2>Inscription</h2>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="password" name="mdp" placeholder="Mot de passe" required>
    <button type="submit" name="register">S'inscrire</button>
</form>

<a href="login.php">Se connecter</a>
