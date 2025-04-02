<?php
// invitations.php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header("Location: connexion.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Si la requête est en POST, traiter l'AJAX et renvoyer le JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['invitation_id'])) {
    $action = $_POST['action'];
    $invId = $_POST['invitation_id'];
    $response = ['success' => false];
    if ($action == 'accept') {
        $updateSql = "UPDATE invitations SET status='accepte' WHERE id='$invId' AND receiver_id='$userId'";
        mysqli_query($conn, $updateSql);
        if (mysqli_affected_rows($conn) > 0) {
            $response['success'] = true;
        }
    } elseif ($action == 'refuse') {
        $updateSql = "UPDATE invitations SET status='refuse' WHERE id='$invId' AND receiver_id='$userId'";
        mysqli_query($conn, $updateSql);
        if (mysqli_affected_rows($conn) > 0) {
            $response['success'] = true;
        }
    }
    // Récupérer la liste actualisée des invitations en attente
    $pendingInvitations = [];
    $sqlPending = "SELECT i.id as invitation_id, u.nom, u.prenom, u.photo
                   FROM invitations i
                   JOIN users u ON i.sender_id = u.id
                   WHERE i.receiver_id = '$userId'
                   AND i.status = 'en_attente'";
    $resPending = mysqli_query($conn, $sqlPending);
    if ($resPending) {
        while ($row = mysqli_fetch_assoc($resPending)) {
            $pendingInvitations[] = $row;
        }
    }
    $response['pendingInvitations'] = $pendingInvitations;
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Traitement des actions en GET (envoi, acceptation ou refus via lien)
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Envoi d'invitation
    if ($action == 'send' && isset($_GET['receiver_id'])) {
        $receiverId = $_GET['receiver_id'];
        $checkSql = "SELECT * FROM invitations 
                     WHERE (sender_id='$userId' AND receiver_id='$receiverId')
                     OR (sender_id='$receiverId' AND receiver_id='$userId')";
        $checkRes = mysqli_query($conn, $checkSql);
        if ($checkRes && mysqli_num_rows($checkRes) == 0) {
            $insertSql = "INSERT INTO invitations (sender_id, receiver_id, status)
                          VALUES ('$userId', '$receiverId', 'en_attente')";
            mysqli_query($conn, $insertSql);
        }
        header("Location: invitations.php");
        exit();
    }

    // Accepter une invitation (GET)
    if ($action == 'accept' && isset($_GET['invitation_id'])) {
        $invId = $_GET['invitation_id'];
        $updateSql = "UPDATE invitations SET status='accepte' WHERE id='$invId' AND receiver_id='$userId'";
        mysqli_query($conn, $updateSql);
        header("Location: invitations.php");
        exit();
    }

    // Refuser une invitation (GET)
    if ($action == 'refuse' && isset($_GET['invitation_id'])) {
        $invId = $_GET['invitation_id'];
        $updateSql = "UPDATE invitations SET status='refuse' WHERE id='$invId' AND receiver_id='$userId'";
        mysqli_query($conn, $updateSql);
        header("Location: invitations.php");
        exit();
    }
}

// Récupérer la liste des invitations en attente (GET)
$pendingInvitations = [];
$sqlPending = "SELECT i.id as invitation_id, u.nom, u.prenom, u.photo
               FROM invitations i
               JOIN users u ON i.sender_id = u.id
               WHERE i.receiver_id = '$userId'
               AND i.status = 'en_attente'";
$resPending = mysqli_query($conn, $sqlPending);
if ($resPending) {
    while ($row = mysqli_fetch_assoc($resPending)) {
        $pendingInvitations[] = $row;
    }
}

// Récupérer la liste des amis (invitations acceptées)
$friends = [];
// In friends.php
$sqlFriends = "SELECT u.id, u.nom, u.prenom, u.photo FROM invitations i
    JOIN users u ON (i.sender_id = '$userId' AND i.receiver_id = u.id)
    OR (i.receiver_id = '$userId' AND i.sender_id = u.id)
    WHERE i.status='accepte'
    AND u.id != '$userId'";
$resFriends = mysqli_query($conn, $sqlFriends);
if ($resFriends) {
    while ($row = mysqli_fetch_assoc($resFriends)) {
        $friends[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
            <a href="invitations.php" class="active">Invitations</a>
            <a href="friends.php">Amis</a>
            <a href="search.php">Recherche</a>
        </div>

        <!-- Content -->
        <div class="content">
            <h1>Gérer mes invitations</h1>
            
            <h2>Invitations reçues (en attente)</h2>
            <div id="pendingInvList">
                <?php if(!empty($pendingInvitations)): ?>
                    <?php foreach ($pendingInvitations as $inv): ?>
                        <div class="card">
                            <img src="uploads/<?php echo $inv['photo']; ?>" alt="Photo Profil">
                            <div class="info">
                                <h3><?php echo $inv['prenom']." ".$inv['nom']; ?></h3>
                            </div>
                            <div class="action">
                                <button onclick="traiterInvitation('accept', <?php echo $inv['invitation_id']; ?>)">Accepter</button>
                                <button onclick="traiterInvitation('refuse', <?php echo $inv['invitation_id']; ?>)" style="background-color: #f44336;">Refuser</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="message">Vous n'avez pas d'invitations en attente.</div>
                <?php endif; ?>
            </div>
            
            
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $f): ?>
                    
                <?php endforeach; ?>
            <?php else: ?>
                <div class="message">Vous n'avez pas encore d'amis.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Fonction JavaScript pour traiter les invitations par AJAX
        function traiterInvitation(action, invitationId) {
            var formData = new FormData();
            formData.append('action', action);
            formData.append('invitation_id', invitationId);

            fetch('invitations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mise à jour dynamique de la liste des invitations
                    let pendingList = document.getElementById('pendingInvList');
                    pendingList.innerHTML = '';
                    data.pendingInvitations.forEach(function(inv) {
                        let card = document.createElement('div');
                        card.className = 'card';
                        
                        let img = document.createElement('img');
                        img.src = 'uploads/' + inv.photo;
                        img.alt = 'Photo Profil';
                        
                        let info = document.createElement('div');
                        info.className = 'info';
                        info.textContent = inv.prenom + ' ' + inv.nom;
                        
                        let actionDiv = document.createElement('div');
                        actionDiv.className = 'action';
                        actionDiv.innerHTML = `<a href="#" onclick="traiterInvitation('accept', ${inv.invitation_id})">
                                                    <button>Accepter</button>
                                                </a>
                                                <a href="#" onclick="traiterInvitation('refuse', ${inv.invitation_id})">
                                                    <button>Refuser</button>
                                                </a>`;
                        
                        card.appendChild(img);
                        card.appendChild(info);
                        card.appendChild(actionDiv);
                        
                        pendingList.appendChild(card);
                    });
                } else {
                    alert("Erreur lors de la mise à jour de l'invitation.");
                }
            })
            .catch(error => {
                alert("Erreur lors de la mise à jour de l'invitation.");
            });
        }
    </script>
</body>
</html>