<?php
// inscription.php
session_start();
include 'db.php';

if (isset($_POST['inscription'])) {
    // Récupération et sécurisation des données
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']); // Utilisation de MD5
    $filieres = isset($_POST['filiere']) ? $_POST['filiere'] : [];
    $filiere = implode(',', $filieres);
    $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
    $sexe = mysqli_real_escape_string($conn, $_POST['sexe']);
    $interets = isset($_POST['interets']) ? $_POST['interets'] : [];
    $interets_str = implode(',', $interets);
    $date_naissance = mysqli_real_escape_string($conn, $_POST['date_naissance']);

    // Gestion de l'upload de la photo
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo = mysqli_real_escape_string($conn, $filename); // Échapper le nom du fichier
        }
    }

    // Construction de la requête SQL
    $sql = "INSERT INTO users (nom, prenom, email, password, filiere, adresse, sexe, interets, date_naissance, photo, role)
            VALUES ('$nom', '$prenom', '$email', '$password', '$filiere', '$adresse', '$sexe', '$interets_str', '$date_naissance', '$photo', 'etudiant')";

    // Exécution de la requête
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: connexion.php?inscription=ok");
        exit();
    } else {
        echo "Erreur lors de l'inscription : " . mysqli_error($conn); // Afficher l'erreur SQL
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Etudiant</title>
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
        padding: 40px 20px;
    }

    .auth-container {
        width: 600px;
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

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="date"],
    select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    input:focus, select:focus {
        border-color: #1d7bcf;
        outline: none;
    }

    /* Style pour les options radio et checkbox */
    .radio-group, .checkbox-group {
        margin-top: 5px;
    }

    .radio-option, .checkbox-option {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
        margin-bottom: 5px;
    }

    input[type="radio"], input[type="checkbox"] {
        margin-right: 5px;
    }

    /* Style pour le sélecteur multiple */
    select[multiple] {
        height: 120px;
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
        <h1>Inscription</h1>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" required>
            </div>

            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="filiere">Filière (multichoix) :</label>
                <select id="filiere" name="filiere[]" multiple required>
                    <option value="DSI">DSI</option>
                    <option value="DAI">DAI</option>
                    <option value="MPE">MPE</option>
                    <option value="MPI">MPI</option>
                    <option value="SRI">SRI</option>
                    <option value="CPI">CPI</option>
                    <option value="MI">MI</option>
                </select>
            </div>

            <div class="form-group">
                <label for="adresse">Adresse :</label>
                <input type="text" id="adresse" name="adresse" required>
            </div>

            <div class="form-group">
                <label>Sexe :</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="sexe" value="Homme" required> Homme
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="sexe" value="Femme" required> Femme
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Intérêts :</label>
                <div class="checkbox-group">
                    <label class="checkbox-option">
                        <input type="checkbox" name="interets[]" value="sport"> Sport
                    </label>
                    <label class="checkbox-option">
                        <input type="checkbox" name="interets[]" value="lecture"> Lecture
                    </label>
                    <label class="checkbox-option">
                        <input type="checkbox" name="interets[]" value="natation"> Natation
                    </label>
                    <label class="checkbox-option">
                        <input type="checkbox" name="interets[]" value="voyage"> Voyage
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="date_naissance">Date de naissance :</label>
                <input type="date" id="date_naissance" name="date_naissance" required>
            </div>

            <div class="form-group">
                <label for="photo">Photo de profil :</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
            </div>
            
            <button type="submit" name="inscription">S'inscrire</button>
        </form>
        
        <p>Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
    </div>
</body>
</html>