<?php
// connexion.php
session_start();
include 'db.php';

if(isset($_POST['connexion'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password' AND role='etudiant'";
    $result = mysqli_query($conn, $sql);
    if($result && mysqli_num_rows($result)==1){
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['photo'] = $user['photo'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Identifiants invalides ou compte inexistant.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Etudiant</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Arial', sans-serif;
    }

    body {
        background-color: #f9fafb;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .auth-container {
        width: 400px;
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .auth-container h1 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }

    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    input:focus {
        border-color: #1d7bcf;
        outline: none;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #1d7bcf;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        font-size: 16px;
        margin-top: 10px;
    }

    button:hover {
        background-color: #1565c0;
    }

    .error {
        color: #e53935;
        margin-bottom: 15px;
        text-align: center;
    }

    p {
        text-align: center;
        margin-top: 20px;
    }

    a {
        color: #1d7bcf;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Connexion</h1>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <form action="" method="post">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="connexion">Se connecter</button>
        </form>
        
        <p>Pas de compte ? <a href="inscription.php">S'inscrire</a></p>
    </div>
</body>
</html>