
let chatWindows = {}; // Object to store all open chats
let pollingInterval = null;

// Initializer the chat system
function initChat() {
    // Start global message polling
    startGlobalMessagePolling();
}

// Open chat with a friend
function openChatWithFriend(friendId, friendName, friendPhoto) {
    // Check if chat is already open
    if (chatWindows[friendId]) {
        // If minimized, expand it
        if (chatWindows[friendId].minimized) {
            toggleChat(friendId);
        }
        return;
    }
    
    // Create new chat window
    const chatContainer = document.createElement('div');
    chatContainer.id = `chatContainer-${friendId}`;
    chatContainer.className = 'chat-container';
    chatContainer.dataset.friendId = friendId;
    
    // Position the chat window based on how many are already open
    const openChatCount = Object.keys(chatWindows).length;
    chatContainer.style.right = (320 * openChatCount + 20) + 'px';
    
    chatContainer.innerHTML = `
        <div id="chatHeader-${friendId}" class="chat-header">
            <div class="chat-header-info" onclick="window.location.href='user_profile.php?id=${friendId}'" style="cursor: pointer;">
                <img src="uploads/${friendPhoto}" alt="Photo de ${friendName}" class="chat-header-photo">
                <h3 id="chatFriendName-${friendId}">${friendName}</h3>
            </div>
            <div class="chat-controls">
                <button class="chat-toggle" onclick="toggleChat(${friendId})">
                    <i id="chatToggleIcon-${friendId}" class="fas fa-chevron-down"></i>
                </button>
                <button class="chat-close" onclick="closeChat(${friendId})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div id="chatMessages-${friendId}" class="chat-messages"></div>
        <div id="chatInput-${friendId}" class="chat-input">
            <input type="text" id="messageInput-${friendId}" placeholder="Saisissez votre message..." 
                   onkeypress="if(event.keyCode === 13) sendMessage(${friendId})">
            <button onclick="sendMessage(${friendId})">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(chatContainer);
    
    // Register the chat window
    chatWindows[friendId] = {
        element: chatContainer,
        minimized: false,
        lastMessageTimestamp: null
    };
    
    // Load existing messages
    loadMessages(friendId);
    
    // Mark messages as read
    markMessagesAsRead(friendId);
    
    // Update badge
    updateSingleFriendBadge(friendId, 0);
}

// Load messages for a specific chat
function loadMessages(friendId) {
    if (!chatWindows[friendId]) return;
    
    let url = `messaging.php?friend_id=${friendId}`;
    
    // If we have a timestamp of the last message, add it to the query
    if (chatWindows[friendId].lastMessageTimestamp) {
        url += `&after=${chatWindows[friendId].lastMessageTimestamp}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const messagesContainer = document.getElementById(`chatMessages-${friendId}`);
                
                // Only clear if it's the first load
                if (!chatWindows[friendId].lastMessageTimestamp) {
                    messagesContainer.innerHTML = '';
                }
                
                // Get the current user ID from the session
                const currentUserId = getUserId();
                
                // Variable to store the timestamp of the most recent message
                let newestTimestamp = chatWindows[friendId].lastMessageTimestamp;
                
                // Add only new messages
                data.messages.forEach(message => {
                    // Check if the message is already displayed
                    const messageExists = messagesContainer.querySelector(`[data-message-id="${message.id}"]`);
                    if (messageExists) return;
                    
                    const isUserMessage = message.sender_id == currentUserId;
                    const messageClass = isUserMessage ? 'sent' : 'received';
                    
                    const messageElement = document.createElement('div');
                    messageElement.className = `message ${messageClass}`;
                    messageElement.setAttribute('data-message-id', message.id);
                    
                    const date = new Date(message.created_at);
                    const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    
                    messageElement.innerHTML = `
                        <div class="message-content">
                            ${message.message}
                            <div class="message-time">${timeStr}</div>
                        </div>
                    `;
                    
                    messagesContainer.appendChild(messageElement);
                    
                    // Update the timestamp of the most recent message
                    const messageTimestamp = new Date(message.created_at).getTime();
                    if (!newestTimestamp || messageTimestamp > newestTimestamp) {
                        newestTimestamp = messageTimestamp;
                    }
                });
                
                // Update lastMessageTimestamp with the most recent one
                if (newestTimestamp) {
                    chatWindows[friendId].lastMessageTimestamp = newestTimestamp;
                }
                
                // Scroll down only if there are new messages
                if (data.messages.length > 0) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            // Retry after a delay in case of network error
            setTimeout(() => loadMessages(friendId), 3000);
        });
}

// Send a message in a specific chat
function sendMessage(friendId) {
    if (!chatWindows[friendId]) return;
    
    const messageInput = document.getElementById(`messageInput-${friendId}`);
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', friendId);
    formData.append('message', message);
    
    // Clear the input field immediately for better UX
    messageInput.value = '';
    
    fetch('messaging.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add the message to the UI
                const messagesContainer = document.getElementById(`chatMessages-${friendId}`);
                const messageElement = document.createElement('div');
                messageElement.className = 'message sent';
                messageElement.setAttribute('data-message-id', data.message.id);
                
                const date = new Date(data.message.created_at);
                const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                messageElement.innerHTML = `
                    <div class="message-content">
                        ${data.message.message}
                        <div class="message-time">${timeStr}</div>
                    </div>
                `;
                
                messagesContainer.appendChild(messageElement);
                
                // Scroll down
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                // Update the timestamp of the last message
                const messageTimestamp = new Date(data.message.created_at).getTime();
                if (!chatWindows[friendId].lastMessageTimestamp || messageTimestamp > chatWindows[friendId].lastMessageTimestamp) {
                    chatWindows[friendId].lastMessageTimestamp = messageTimestamp;
                }
            } else {
                // Display an error message
                alert("Error sending message: " + (data.message || "Unknown error"));
                
                // Put the message back in the input field
                messageInput.value = message;
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert("Network error. Please try again.");
            // Put the message back in the input field
            messageInput.value = message;
        });
}

// Mark messages as read for a specific chat
function markMessagesAsRead(friendId) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('friend_id', friendId);
    
    fetch('messaging.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Visually update the badges for this contact
                updateSingleFriendBadge(friendId, 0);
            }
        })
        .catch(error => console.error('Error marking messages as read:', error));
}

// Minimize/expand a specific chat
function toggleChat(friendId) {
    if (!chatWindows[friendId]) return;
    
    const chatContainer = document.getElementById(`chatContainer-${friendId}`);
    const chatMessages = document.getElementById(`chatMessages-${friendId}`);
    const chatInput = document.getElementById(`chatInput-${friendId}`);
    const chatToggleIcon = document.getElementById(`chatToggleIcon-${friendId}`);
    
    if (!chatWindows[friendId].minimized) {
        chatContainer.classList.add('chat-collapsed');
        chatMessages.style.display = 'none';
        chatInput.style.display = 'none';
        chatToggleIcon.className = 'fas fa-chevron-up';
    } else {
        chatContainer.classList.remove('chat-collapsed');
        chatMessages.style.display = 'block';
        chatInput.style.display = 'flex';
        chatToggleIcon.className = 'fas fa-chevron-down';
        
        // Reload messages when opening
        loadMessages(friendId);
        markMessagesAsRead(friendId);
    }
    
    chatWindows[friendId].minimized = !chatWindows[friendId].minimized;
}

// Close a specific chat
function closeChat(friendId) {
    if (!chatWindows[friendId]) return;
    
    const chatContainer = chatWindows[friendId].element;
    chatContainer.remove();
    
    delete chatWindows[friendId];
    
    // Reposition remaining chat windows
    repositionChatWindows();
}

// Reposition chat windows after closing one
function repositionChatWindows() {
    const friendIds = Object.keys(chatWindows);
    friendIds.forEach((friendId, index) => {
        const chatContainer = chatWindows[friendId].element;
        chatContainer.style.right = (320 * index + 20) + 'px';
    });
}

// Check for new messages globally
function startGlobalMessagePolling() {
    // Clear existing interval
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    // Function to perform checks
    const performChecks = () => {
        // Check for each open chat
        for (const friendId in chatWindows) {
            if (!chatWindows[friendId].minimized) {
                loadMessages(friendId);
                markMessagesAsRead(friendId);
            }
        }
        
        // Check globally for new messages to update badges
        checkAllNewMessages();
    };
    
    // Perform an initial check immediately
    performChecks();
    
    // Then check for new messages every 5 seconds
    pollingInterval = setInterval(performChecks, 5000);
}

// Check all new messages to update badges
function checkAllNewMessages() {
    fetch('messaging.php?action=check_all_new')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update badges on the page
                updateUnreadBadges(data.newMessages);
            }
        })
        .catch(error => console.error('Error checking for new messages:', error));
}

// Update unread message badges on the page
function updateUnreadBadges(newMessages) {
    // Create an object to count unread messages by friend
    const unreadCounts = {};
    
    // Initialize all counters to 0
    const userCards = document.querySelectorAll('.user-card');
    userCards.forEach(card => {
        const friendId = card.getAttribute('data-friend-id');
        if (friendId) unreadCounts[friendId] = 0;
    });
    
    // Count unread messages
    newMessages.forEach(message => {
        const senderId = message.sender_id;
        if (unreadCounts[senderId] !== undefined) {
            unreadCounts[senderId]++;
        }
    });
    
    // Update badges on friend cards
    userCards.forEach(card => {
        const friendId = card.getAttribute('data-friend-id');
        if (friendId) updateSingleFriendBadge(friendId, unreadCounts[friendId] || 0);
    });
}

// Update the badge of a single friend
function updateSingleFriendBadge(friendId, count) {
    const cards = document.querySelectorAll(`.user-card[data-friend-id="${friendId}"]`);
    
    cards.forEach(card => {
        if (!card) return;
        
        const badgeElement = card.querySelector('.unread-badge');
        
        if (count > 0) {
            // Create or update the badge
            if (badgeElement) {
                badgeElement.textContent = count;
                badgeElement.style.display = 'inline-block';
            } else {
                const nameElement = card.querySelector('h3');
                if (nameElement) {
                    const badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.textContent = count;
                    nameElement.appendChild(badge);
                }
            }
        } else if (badgeElement) {
            // Hide the badge if there are no unread messages
            badgeElement.style.display = 'none';
        }
    });
}

// Get the current user ID
function getUserId() {
    return document.body.getAttribute('data-user-id');
}

// Initialize the chat system when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Make sure the userId variable is defined
    if (typeof userId === 'undefined') {
        console.error('The userId variable is not defined. The chat will not work properly.');
        return;
    }
    
    // Add the user ID to the body for the getUserId function
    document.body.setAttribute('data-user-id', userId);
    
    // Initialize the chat system
    initChat();
}); 