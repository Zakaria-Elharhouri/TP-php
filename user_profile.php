<?php
// user_profile.php

include 'db.php';
include 'posts.php'; // Inclure le fichier posts.php pour accéder aux fonctions

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'etudiant') {
    header("Location: connexion.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Vérifier si l'ID de l'utilisateur à afficher est fourni
if (!isset($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$profileId = mysqli_real_escape_string($conn, $_GET['id']);

// Récupérer les informations de l'utilisateur
$sql = "SELECT * FROM users WHERE id='$profileId'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: search.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Vérifier le statut d'amitié
$friendStatus = 'none';
$checkFriendSql = "SELECT status FROM invitations 
                  WHERE ((sender_id='$userId' AND receiver_id='$profileId') 
                  OR (sender_id='$profileId' AND receiver_id='$userId'))";
$checkFriendRes = mysqli_query($conn, $checkFriendSql);

if (mysqli_num_rows($checkFriendRes) > 0) {
    $friendData = mysqli_fetch_assoc($checkFriendRes);
    $friendStatus = $friendData['status'];
}

// Récupérer les posts de l'utilisateur visité
$userPosts = getPosts($conn, $profileId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo $user['prenom'].' '.$user['nom']; ?></title>
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
            <a href="search.php">Recherche</a>
        </div>

        <!-- Content -->
        <div class="content">
            <h1>Profil de <?php echo $user['prenom'].' '.$user['nom']; ?></h1>
            
            <div class="user-profile">
                <img src="uploads/<?php echo $user['photo']; ?>" alt="Photo Profil" class="profile-picture">
                
                <div class="profile-info">
                    <div>
                        <span>Nom:</span>
                        <p><?php echo $user['nom']; ?></p>
                    </div>
                    <div>
                        <span>Prénom:</span>
                        <p><?php echo $user['prenom']; ?></p>
                    </div>
                    <div>
                        <span>Date de naissance:</span>
                        <p><?php echo $user['date_naissance']; ?></p>
                    </div>
                    <div>
                        <span>Filière:</span>
                        <p><?php echo $user['filiere']; ?></p>
                    </div>
                    <div>
                        <span>Adresse:</span>
                        <p><?php echo $user['adresse']; ?></p>
                    </div>
                    <div>
                        <span>Intérêts:</span>
                        <p><?php echo $user['interets']; ?></p>
                    </div>
                    
                    <?php if($friendStatus == 'accepte'): ?>
                        <button class="chat-btn alt profile-action-btn" onclick="openChatWithFriend(<?php echo $user['id']; ?>, '<?php echo $user['prenom'].' '.$user['nom']; ?>', '<?php echo $user['photo']; ?>')">
                            <i class="fas fa-comment"></i> Chatter
                        </button>
                    <?php elseif($friendStatus == 'en_attente'): ?>
                        <div class="pending-invite profile-action-btn">Invitation en attente</div>
                    <?php elseif($friendStatus == 'none' && $user['id'] != $userId): ?>
                        <a href="invitations.php?action=send&receiver_id=<?php echo $user['id']; ?>" class="action-button invite-button profile-action-btn">
                            <i class="fas fa-user-plus"></i> Ajouter
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2 class="section-title">Publications de <?php echo $user['prenom']; ?></h2>
            
            <!-- Affichage des publications -->
            <div class="posts-container">
                <?php if(!empty($userPosts)): ?>
                    <?php foreach($userPosts as $post): ?>
                        <div class="post" id="post-<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <a href="user_profile.php?id=<?php echo $post['user_id']; ?>" class="post-user">
                                    <img src="uploads/<?php echo $post['photo']; ?>" alt="Photo de <?php echo $post['prenom'].' '.$post['nom']; ?>">
                                    <div class="post-user-info">
                                        <h3><?php echo $post['prenom'].' '.$post['nom']; ?></h3>
                                        <span class="post-date">
                                            <?php 
                                                $date = new DateTime($post['created_at']);
                                                echo $date->format('d/m/Y à H:i'); 
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php if($post['image']): ?>
                                    <div class="post-image">
                                        <img src="uploads/posts/<?php echo $post['image']; ?>" alt="Image de publication">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-actions">
                                <div class="post-reactions">
                                    <span class="reaction-count">
                                        <i class="fas fa-thumbs-up"></i> <span id="likes-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span>
                                    </span>
                                    <span class="reaction-count">
                                        <i class="fas fa-thumbs-down"></i> <span id="dislikes-count-<?php echo $post['id']; ?>"><?php echo $post['dislikes_count']; ?></span>
                                    </span>
                                    <span class="comments-count"><?php echo $post['comments_count']; ?> commentaires</span>
                                </div>
                                <div class="post-buttons">
                                    <button class="reaction-btn <?php echo $post['user_reaction'] === 'like' ? 'active' : ''; ?>" 
                                            onclick="reactToPost(<?php echo $post['id']; ?>, 'like')" 
                                            id="like-btn-<?php echo $post['id']; ?>">
                                        <i class="fas fa-thumbs-up"></i> J'aime
                                    </button>
                                    <button class="reaction-btn <?php echo $post['user_reaction'] === 'dislike' ? 'active' : ''; ?>" 
                                            onclick="reactToPost(<?php echo $post['id']; ?>, 'dislike')" 
                                            id="dislike-btn-<?php echo $post['id']; ?>">
                                        <i class="fas fa-thumbs-down"></i> Je n'aime pas
                                    </button>
                                    <button class="comment-btn" onclick="focusCommentInput(<?php echo $post['id']; ?>)">
                                        <i class="fas fa-comment"></i> Commenter
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Section des commentaires -->
                            <div class="post-comments">
                                <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>">
                                    <?php 
                                        $comments = getComments($conn, $post['id']);
                                        foreach($comments as $comment): 
                                    ?>
                                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                                            <div class="comment-user">
                                                <img src="uploads/<?php echo $comment['photo']; ?>" alt="Photo de <?php echo $comment['prenom'].' '.$comment['nom']; ?>">
                                            </div>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <a href="user_profile.php?id=<?php echo $comment['user_id']; ?>"><?php echo $comment['prenom'].' '.$comment['nom']; ?></a>
                                                    <span class="comment-date">
                                                        <?php 
                                                            $date = new DateTime($comment['created_at']);
                                                            echo $date->format('d/m/Y à H:i'); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Formulaire pour ajouter un commentaire -->
                                <div class="comment-form">
                                    <img src="uploads/<?php echo $_SESSION['photo']; ?>" alt="Votre photo">
                                    <form id="comment-form-<?php echo $post['id']; ?>" onsubmit="submitComment(event, <?php echo $post['id']; ?>)">
                                        <input type="hidden" name="action" value="comment">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="content" id="comment-input-<?php echo $post['id']; ?>" placeholder="Écrivez un commentaire..." required></textarea>
                                        <button type="submit">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-posts">
                        <p>Cet utilisateur n'a pas encore publié de contenu.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Variable pour identifier l'utilisateur connecté
        const userId = <?php echo $userId; ?>;

        // Fonction pour réagir à une publication (like/dislike)
        function reactToPost(postId, reactionType) {
            const formData = new FormData();
            formData.append('action', reactionType);
            formData.append('post_id', postId);
            
            fetch('posts.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour les compteurs
                    document.getElementById(`likes-count-${postId}`).textContent = data.likes;
                    document.getElementById(`dislikes-count-${postId}`).textContent = data.dislikes;
                    
                    // Mettre à jour les classes des boutons
                    const likeBtn = document.getElementById(`like-btn-${postId}`);
                    const dislikeBtn = document.getElementById(`dislike-btn-${postId}`);
                    
                    likeBtn.classList.remove('active');
                    dislikeBtn.classList.remove('active');
                    
                    if (data.userStatus === 'like') {
                        likeBtn.classList.add('active');
                    } else if (data.userStatus === 'dislike') {
                        dislikeBtn.classList.add('active');
                    }
                }
            })
            .catch(error => console.error('Erreur:', error));
        }
        
        // Fonction pour se concentrer sur le champ de commentaire
        function focusCommentInput(postId) {
            document.getElementById(`comment-input-${postId}`).focus();
        }
        
        // Fonction pour soumettre un commentaire
        function submitComment(event, postId) {
            event.preventDefault();
            
            const form = document.getElementById(`comment-form-${postId}`);
            const formData = new FormData(form);
            
            fetch('posts.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Créer et ajouter le nouveau commentaire
                    const commentsList = document.getElementById(`comments-list-${postId}`);
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.id = `comment-${data.comment.id}`;
                    
                    const date = new Date(data.comment.created_at);
                    const formattedDate = date.toLocaleDateString('fr-FR') + ' à ' + 
                                         date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                    
                    newComment.innerHTML = `
                        <div class="comment-user">
                            <img src="uploads/${data.comment.user_photo}" alt="Photo de ${data.comment.user_name}">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <a href="user_profile.php?id=${data.comment.user_id}">${data.comment.user_name}</a>
                                <span class="comment-date">${formattedDate}</span>
                            </div>
                            <p>${data.comment.content.replace(/\n/g, '<br>')}</p>
                        </div>
                    `;
                    
                    commentsList.appendChild(newComment);
                    form.reset();
                    
                    // Mettre à jour le compteur de commentaires
                    const commentCountElement = form.closest('.post').querySelector('.comments-count');
                    const currentCount = parseInt(commentCountElement.textContent);
                    commentCountElement.textContent = (currentCount + 1) + ' commentaires';
                }
            })
            .catch(error => console.error('Erreur:', error));
        }
    </script>
    <script src="chat.js"></script>
</body>
</html>