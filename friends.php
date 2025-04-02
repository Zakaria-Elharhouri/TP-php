<?php

session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header("Location: connexion.php");
    exit();
}

$userId = $_SESSION['user_id'];
$friends = [];

$sqlFriends = "SELECT u.id, u.nom, u.prenom, u.photo FROM invitations i
    JOIN users u ON (i.sender_id = '$userId' AND i.receiver_id = u.id)
    OR (i.receiver_id = '$userId' AND i.sender_id = u.id)
    WHERE i.status='accepte'
    AND u.id != '$userId'";
$resFriends = mysqli_query($conn, $sqlFriends);
if ($resFriends) {
    while ($row = mysqli_fetch_assoc($resFriends)) {
        // Récupérer le nombre de messages non lus de cet ami
        $unreadSql = "SELECT COUNT(*) as unread FROM messages 
                      WHERE sender_id = '{$row['id']}' AND receiver_id = '$userId' AND read_status = 0";
        $unreadResult = mysqli_query($conn, $unreadSql);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $row['unread'] = $unreadData['unread'];
        
        $friends[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Amis</title>
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

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
      
        <div class="sidebar">
        <a href="index.php">Accueil</a>
            <a href="profile.php">Profile</a>
            <a href="invitations.php">Invitations</a>
            <a href="friends.php" class="active">Amis</a>
            <a href="search.php">Recherche</a>
        </div>

        
        <div class="content">
            <h1>Mes Amis</h1>
            
            <?php if (!empty($friends)): ?>
                <div class="users-grid">
                    <?php foreach ($friends as $friend): ?>
                        <div class="user-card" onclick="openChatWithFriend(<?php echo $friend['id']; ?>, '<?php echo $friend['prenom'].' '.$friend['nom']; ?>', '<?php echo $friend['photo']; ?>')">
                            <img src="uploads/<?php echo $friend['photo']; ?>" alt="Photo Profil">
                            <h3>
                                <?php echo $friend['prenom']." ".$friend['nom']; ?>
                                <?php if($friend['unread'] > 0): ?>
                                    <span class="unread-badge"><?php echo $friend['unread']; ?></span>
                                <?php endif; ?>
                            </h3>
                            <button class="chat-btn alt">
                                <i class="fas fa-comment"></i> Chatter
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="message">Vous n'avez pas encore d'amis.</div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="chatContainer" class="chat-container" style="display: none;">
        <div id="chatHeader" class="chat-header">
            <h3 id="chatFriendName"></h3>
            <button class="chat-toggle" onclick="toggleChat()">
                <i id="chatToggleIcon" class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div id="chatMessages" class="chat-messages"></div>
        <div id="chatInput" class="chat-input">
            <input type="text" id="messageInput" placeholder="Saisissez votre message..." onkeypress="if(event.keyCode === 13) sendMessage()">
            <button onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
    
        const userId = <?php echo $userId; ?>;
    </script>
    <script src="chat.js"></script>
</body>
</html>