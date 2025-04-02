<?php
// posts.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Fonction pour obtenir les informations de l'utilisateur
function getUserInfo($conn, $userId) {
    $sql = "SELECT nom, prenom, photo FROM users WHERE id = '$userId'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Publier un nouveau post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $user_id = $_SESSION['user_id'];
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $image = null;
    
    // Vérifier si une image a été téléchargée
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Vérifier le type de fichier
        if (in_array(strtolower($filetype), $allowed)) {
            // Générer un nom unique pour l'image
            $newname = uniqid() . '.' . $filetype;
            $destination = 'uploads/posts/' . $newname;
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists('uploads/posts/')) {
                mkdir('uploads/posts/', 0777, true);
            }
            
            // Déplacer le fichier téléchargé
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = $newname;
            }
        }
    }
    
    // Insérer le post dans la base de données
    $query = "INSERT INTO posts (user_id, content, image) VALUES ('$user_id', '$content', " . ($image ? "'$image'" : "NULL") . ")";
    
    if (mysqli_query($conn, $query)) {
        // Rediriger vers la page d'accueil
        header("Location: index.php?msg=post_success");
    } else {
        // Rediriger avec un message d'erreur
        header("Location: index.php?error=post_error");
    }
    exit();
}

// Ajouter un like ou dislike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'like' || $_POST['action'] === 'dislike')) {
    $user_id = $_SESSION['user_id'];
    $post_id = (int)$_POST['post_id'];
    $type = $_POST['action'] === 'like' ? 'like' : 'dislike';
    
    // Vérifier si l'utilisateur a déjà liké/disliké ce post
    $checkQuery = "SELECT * FROM likes WHERE user_id = '$user_id' AND post_id = '$post_id'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        $existingLike = mysqli_fetch_assoc($checkResult);
        if ($existingLike['type'] === $type) {
            // Si c'est le même type, on supprime (like puis re-like = annuler le like)
            $query = "DELETE FROM likes WHERE id = '{$existingLike['id']}'";
        } else {
            // Si c'est un type différent, on met à jour
            $query = "UPDATE likes SET type = '$type' WHERE id = '{$existingLike['id']}'";
        }
    } else {
        // Sinon, on insère un nouveau like/dislike
        $query = "INSERT INTO likes (user_id, post_id, type) VALUES ('$user_id', '$post_id', '$type')";
    }
    
    if (mysqli_query($conn, $query)) {
        // Si c'est une requête AJAX, renvoyer un JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Récupérer les nouveaux compteurs
            $likesQuery = "SELECT COUNT(*) as count FROM likes WHERE post_id = '$post_id' AND type = 'like'";
            $dislikesQuery = "SELECT COUNT(*) as count FROM likes WHERE post_id = '$post_id' AND type = 'dislike'";
            
            $likesResult = mysqli_query($conn, $likesQuery);
            $dislikesResult = mysqli_query($conn, $dislikesQuery);
            
            $likesCount = mysqli_fetch_assoc($likesResult)['count'];
            $dislikesCount = mysqli_fetch_assoc($dislikesResult)['count'];
            
            // Vérifier le statut actuel de l'utilisateur
            $userStatusQuery = "SELECT type FROM likes WHERE user_id = '$user_id' AND post_id = '$post_id'";
            $userStatusResult = mysqli_query($conn, $userStatusQuery);
            $userStatus = mysqli_num_rows($userStatusResult) > 0 ? mysqli_fetch_assoc($userStatusResult)['type'] : null;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'likes' => $likesCount,
                'dislikes' => $dislikesCount,
                'userStatus' => $userStatus
            ]);
        } else {
            // Rediriger vers la page précédente
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erreur lors de l\'ajout du like/dislike']);
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=like_error");
        }
    }
    exit();
}

// Ajouter un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $user_id = $_SESSION['user_id'];
    $post_id = (int)$_POST['post_id'];
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    
    if (empty($content)) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=empty_comment");
        exit();
    }
    
    $query = "INSERT INTO comments (user_id, post_id, content) VALUES ('$user_id', '$post_id', '$content')";
    
    if (mysqli_query($conn, $query)) {
        $comment_id = mysqli_insert_id($conn);
        
        // Si c'est une requête AJAX, renvoyer le commentaire ajouté
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            $user = getUserInfo($conn, $user_id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $comment_id,
                    'user_id' => $user_id,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s'),
                    'user_name' => $user['prenom'] . ' ' . $user['nom'],
                    'user_photo' => $user['photo']
                ]
            ]);
        } else {
            // Rediriger vers la page précédente
            header("Location: " . $_SERVER['HTTP_REFERER'] . "#comment-" . $comment_id);
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erreur lors de l\'ajout du commentaire']);
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=comment_error");
        }
    }
    exit();
}

// Fonction pour récupérer les posts (utilisée par les différentes pages)
function getPosts($conn, $userId = null, $limit = 50, $offset = 0) {
    $userCondition = $userId ? "WHERE p.user_id = '$userId'" : "";
    
    $query = "SELECT p.*, u.nom, u.prenom, u.photo,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as likes_count,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislikes_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
              FROM posts p
              JOIN users u ON p.user_id = u.id
              $userCondition
              ORDER BY p.created_at DESC
              LIMIT $limit OFFSET $offset";
              
    $result = mysqli_query($conn, $query);
    $posts = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Pour chaque post, vérifier si l'utilisateur connecté a liké/disliké
            $currentUserId = $_SESSION['user_id'];
            $likeQuery = "SELECT type FROM likes WHERE user_id = '$currentUserId' AND post_id = '{$row['id']}'";
            $likeResult = mysqli_query($conn, $likeQuery);
            $row['user_reaction'] = mysqli_num_rows($likeResult) > 0 ? mysqli_fetch_assoc($likeResult)['type'] : null;
            
            $posts[] = $row;
        }
    }
    
    return $posts;
}

// Fonction pour récupérer les commentaires d'un post
function getComments($conn, $postId) {
    $query = "SELECT c.*, u.nom, u.prenom, u.photo
              FROM comments c
              JOIN users u ON c.user_id = u.id
              WHERE c.post_id = '$postId'
              ORDER BY c.created_at ASC";
              
    $result = mysqli_query($conn, $query);
    $comments = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
    }
    
    return $comments;
}
?>