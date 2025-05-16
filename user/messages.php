<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get selected user information if user_id is provided
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$selected_user = null;

if ($selected_user_id) {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $selected_user = $stmt->fetch();
}

// Get all conversations for the current user
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.username,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = other_user_id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$conversations = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="messages-page">
    <div class="messages-container">
        <div class="conversations-sidebar">
            <div class="sidebar-panels">
                <div class="search-panel">
                    <div class="search-box">
                        <h3>Find Users to Chat</h3>
                        <div class="search-input-wrapper">
                            <input type="text" id="search-input" placeholder="Search users to start a conversation...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div class="search-results" id="search-results"></div>
                    </div>
                </div>

                <div class="conversations-panel">
                    <div class="conversations-list">
                        <h3>Your Conversations</h3>
                        <?php if (empty($conversations)): ?>
                            <p class="no-conversations">No conversations yet. Search for users to start chatting!</p>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item <?php echo $conv['other_user_id'] == $selected_user_id ? 'active' : ''; ?> <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>"
                                     data-user-id="<?php echo $conv['other_user_id']; ?>">
                                    <div class="conversation-info">
                                        <span class="username"><?php echo htmlspecialchars($conv['username']); ?></span>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($selected_user): ?>
                <div class="chat-header">
                    <h3>Chat with <?php echo htmlspecialchars($selected_user['username']); ?></h3>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be loaded here via AJAX -->
                </div>
                <div class="chat-input">
                    <form id="message-form" class="message-form">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="message-input" placeholder="Type your message..." required>
                            <button type="submit" class="send-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-messages">
                    <p>Select a conversation or search for a user to start chatting</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.messages-page {
    padding: 2rem 0;
    height: calc(100vh - 100px);
}

.messages-container {
    display: flex;
    gap: 2rem;
    height: 100%;
    max-width: 1200px;
    margin: 0 auto;
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.conversations-sidebar {
    width: 300px;
    border-right: 1px solid var(--light-color);
    display: flex;
    flex-direction: column;
}

.search-box {
    padding: 1rem;
    border-bottom: 1px solid var(--light-color);
    position: relative;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--white);
    border: 1px solid var(--light-color);
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: none;
    z-index: 1000;
}

.search-result-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
}

.search-result-item:hover {
    background: var(--lightest-color);
}

.conversations-list {
    flex-grow: 1;
    overflow-y: auto;
    padding: 1rem;
}

.conversations-list h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.conversation-item {
    padding: 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-bottom: 0.5rem;
}

.conversation-item:hover {
    background-color: var(--lightest-color);
}

.conversation-item.active {
    background-color: var(--light-color);
}

.conversation-item.unread {
    background-color: var(--lightest-color);
    font-weight: bold;
}

.conversation-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.unread-badge {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.chat-area {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid var(--light-color);
}

.chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message {
    max-width: 70%;
    padding: 0.75rem;
    border-radius: 8px;
    position: relative;
}

.message.sent {
    background-color: var(--primary-color);
    color: var(--white);
    align-self: flex-end;
}

.message.received {
    background-color: var(--light-color);
    color: var(--text-color);
    align-self: flex-start;
}

.message .time {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

.chat-input {
    padding: 1rem;
    border-top: 1px solid var(--light-color);
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.no-messages {
    text-align: center;
    color: var(--text-color);
    opacity: 0.7;
    margin-top: 2rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageForm = document.getElementById('message-form');
    const chatMessages = document.getElementById('chat-messages');
    const conversationItems = document.querySelectorAll('.conversation-item');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let selectedUserId = <?php echo $selected_user_id ? $selected_user_id : 'null'; ?>;
    let searchTimeout = null;

    // Load initial messages if a conversation is selected
    if (selectedUserId) {
        loadMessages();
    }

    // Handle conversation selection
    conversationItems.forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.dataset.userId;
            window.location.href = `messages.php?user_id=${userId}`;
        });
    });

    // Handle user search with debounce
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Clear results if query is empty
        if (query.length < 2) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }
        
        // Show loading state immediately
        searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
        searchResults.style.display = 'block';
        
        // Set new timeout
        searchTimeout = setTimeout(() => {
            fetch(`search_users.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users && data.users.length > 0) {
                        searchResults.innerHTML = '';
                        data.users.forEach(user => {
                            const userDiv = document.createElement('div');
                            userDiv.className = 'search-result-item';
                            
                            // Create user info HTML
                            let userHtml = `<span class="username">${user.username}</span>`;
                            if (user.product_count > 0) {
                                userHtml += `<span class="user-products">${user.product_count} products</span>`;
                            }
                            if (user.has_chat) {
                                userHtml += '<span class="existing-chat">Existing chat</span>';
                            }
                            
                            userDiv.innerHTML = userHtml;
                            
                            userDiv.addEventListener('click', function() {
                                window.location.href = `messages.php?user_id=${user.id}`;
                            });
                            searchResults.appendChild(userDiv);
                        });
                    } else {
                        searchResults.innerHTML = '<div class="no-results">No users found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error searching users:', error);
                    searchResults.innerHTML = '<div class="search-error">Error searching users</div>';
                });
        }, 300); // 300ms debounce delay
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Keep search results visible when clicking inside the search area
    searchInput.addEventListener('click', function(e) {
        if (this.value.trim().length >= 2) {
            searchResults.style.display = 'block';
        }
    });

    // Load messages for selected conversation
    function loadMessages() {
        if (selectedUserId) {
            fetch(`get_messages.php?user_id=${selectedUserId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        chatMessages.innerHTML = '';
                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}`;
                            messageDiv.innerHTML = `
                                <div class="message-content">${msg.message}</div>
                                <div class="time">${formatMessageTime(msg.created_at)}</div>
                            `;
                            chatMessages.appendChild(messageDiv);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }
    }

    // Format message time
    function formatMessageTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        
        if (days === 0) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else if (days === 1) {
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }

    // Handle message submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    loadMessages();
                    // Refresh the conversations list
                    window.location.reload();
                } else {
                    console.error('Error sending message:', data.error);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        });
    }

    // Poll for new messages every 5 seconds
    if (selectedUserId) {
        setInterval(loadMessages, 5000);
    }
});
</script>

<?php include '../includes/footer.php'; ?> 