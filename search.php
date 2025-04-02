<?php
// search.php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header("Location: connexion.php");
    exit();
}

$userId = $_SESSION['user_id'];
$query = '';
$searchResults = [];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $query = mysqli_real_escape_string($conn, $_GET['q']);
    
    // Recherche par nom, prénom ou intérêts
    $sql = "SELECT id, nom, prenom, photo, filiere, interets 
            FROM users 
            WHERE (nom LIKE '%$query%' OR prenom LIKE '%$query%' OR interets LIKE '%$query%') 
            AND id != '$userId' 
            AND role = 'etudiant'";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Vérifier le statut d'amitié
            $checkFriendSql = "SELECT status FROM invitations 
                              WHERE ((sender_id='$userId' AND receiver_id='{$row['id']}') 
                              OR (sender_id='{$row['id']}' AND receiver_id='$userId'))";
            $checkFriendRes = mysqli_query($conn, $checkFriendSql);
            
            if (mysqli_num_rows($checkFriendRes) > 0) {
                $friendStatus = mysqli_fetch_assoc($checkFriendRes);
                $row['friend_status'] = $friendStatus['status'];
            } else {
                $row['friend_status'] = 'none';
            }
            
            $searchResults[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'utilisateurs</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body data-user-id="<?php echo $userId; ?>">
    <!-- NavBar -->
    <div class="navbar">
        <div>
            <?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?>
        </div>
        <div style="display: flex; align-items: center;">
            <a href="profile.php"><img src="uploads/<?php echo $_SESSION['photo']; ?>" alt="Photo Profil"></a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
        <a href="index.php">Accueil</a>
            <a href="profile.php">Profile</a>
            <a href="invitations.php">Invitations</a>
            <a href="friends.php">Amis</a>
            <a href="search.php" class="active">Recherche</a>
        </div>

        <!-- Content -->
        <div class="content">
            <h1>Recherche d'utilisateurs</h1>
            
            <div class="search-form">
                <form method="GET" action="search.php">
                    <input type="text" name="q" placeholder="Rechercher par nom, prénom ou intérêts..." value="<?php echo htmlspecialchars($query); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
                </form>
            </div>
            
            <div class="search-results">
                <?php if (!empty($searchResults)): ?>
                    <h2>Résultats de recherche pour "<?php echo htmlspecialchars($query); ?>"</h2>
                    <div class="users-grid">
                        <?php foreach ($searchResults as $user): ?>
                            <div class="user-card" data-friend-id="<?php echo $user['id']; ?>" onclick="window.location.href='user_profile.php?id=<?php echo $user['id']; ?>'">
                                <img src="uploads/<?php echo $user['photo']; ?>" alt="Photo de profil">
                                <h3><?php echo $user['prenom'].' '.$user['nom']; ?></h3>
                                <p><strong>Filière:</strong> <?php echo $user['filiere']; ?></p>
                                <p><strong>Intérêts:</strong> <?php echo $user['interets']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($_GET['q'])): ?>
                    <div class="message">Aucun résultat trouvé pour "<?php echo htmlspecialchars($query); ?>"</div>
                <?php else: ?>
                    <div class="message">Entrez un terme de recherche pour trouver des utilisateurs.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Variable pour identifier l'utilisateur connecté
        const userId = <?php echo $userId; ?>;
    </script>
    <script src="chat.js"></script>
</body>
</html>