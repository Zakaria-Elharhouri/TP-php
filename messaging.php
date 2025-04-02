<?php

session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Récupérer les messages entre deux utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['friend_id'])) {
    $sender_id = $_SESSION['user_id'];
    $friend_id = (int)$_GET['friend_id'];
    
    // Valider que l'ami existe et qu'ils sont bien amis
    $checkFriend = "SELECT * FROM invitations WHERE 
                    ((sender_id = '$sender_id' AND receiver_id = '$friend_id') OR 
                    (sender_id = '$friend_id' AND receiver_id = '$sender_id')) 
                    AND status = 'accepte'";
    $checkResult = mysqli_query($conn, $checkFriend);
    
    if (mysqli_num_rows($checkResult) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cet utilisateur n\'est pas dans votre liste d\'amis']);
        exit();
    }
    
    // Récupérer les informations de l'ami
    $friendInfoSql = "SELECT id, nom, prenom, photo FROM users WHERE id = '$friend_id'";
    $friendInfoResult = mysqli_query($conn, $friendInfoSql);
    $friendInfo = mysqli_fetch_assoc($friendInfoResult);
    
    // Récupérer les messages
    $messagesSql = "SELECT * FROM messages WHERE 
                    (sender_id = '$sender_id' AND receiver_id = '$friend_id') OR 
                    (sender_id = '$friend_id' AND receiver_id = '$sender_id')
                    ORDER BY created_at ASC";
    $messagesResult = mysqli_query($conn, $messagesSql);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($messagesResult)) {
        $messages[] = $row;
    }
    
    // Marquer les messages non lus comme lus
    $updateSql = "UPDATE messages SET read_status = 1 
                  WHERE sender_id = '$friend_id' AND receiver_id = '$sender_id' AND read_status = 0";
    mysqli_query($conn, $updateSql);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'friend' => $friendInfo,
        'messages' => $messages
    ]);
    exit();
}

// Envoyer un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Le message ne peut pas être vide']);
        exit();
    }
    
    // Vérifier que l'ami existe et qu'ils sont bien amis
    $checkFriend = "SELECT * FROM invitations WHERE 
                    ((sender_id = '$sender_id' AND receiver_id = '$receiver_id') OR 
                    (sender_id = '$receiver_id' AND receiver_id = '$sender_id')) 
                    AND status = 'accepte'";
    $checkResult = mysqli_query($conn, $checkFriend);
    
    if (mysqli_num_rows($checkResult) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cet utilisateur n\'est pas dans votre liste d\'amis']);
        exit();
    }
    
    // Insérer le message
    $insertSql = "INSERT INTO messages (sender_id, receiver_id, message, read_status, created_at) 
                 VALUES ('$sender_id', '$receiver_id', '$message', 0, NOW())";
    
    if (mysqli_query($conn, $insertSql)) {
        $messageId = mysqli_insert_id($conn);
        $messageSql = "SELECT * FROM messages WHERE id = '$messageId'";
        $messageResult = mysqli_query($conn, $messageSql);
        $messageData = mysqli_fetch_assoc($messageResult);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $messageData
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erreur lors de l\'envoi du message']);
    }
    exit();
}

// Vérifier les nouveaux messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_new') {
    $user_id = $_SESSION['user_id'];
    $friendId = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : null;
    
    if ($friendId) {
        // Vérifier les nouveaux messages d'un ami spécifique
        $newMessagesSql = "SELECT * FROM messages 
                          WHERE sender_id = '$friendId' AND receiver_id = '$user_id' AND read_status = 0
                          ORDER BY created_at ASC";
    } else {
        // Vérifier tous les nouveaux messages
        $newMessagesSql = "SELECT m.*, u.nom, u.prenom, u.photo 
                          FROM messages m
                          JOIN users u ON m.sender_id = u.id
                          WHERE m.receiver_id = '$user_id' AND m.read_status = 0
                          ORDER BY m.created_at ASC";
    }
    
    $newMessagesResult = mysqli_query($conn, $newMessagesSql);
    
    $newMessages = [];
    while ($row = mysqli_fetch_assoc($newMessagesResult)) {
        $newMessages[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'newMessages' => $newMessages,
        'count' => count($newMessages)
    ]);
    exit();
}
?>