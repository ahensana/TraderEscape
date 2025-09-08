<?php 
include 'includes/header.php'; 

// Get current user information if logged in
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/auth_functions.php';
    $currentUser = getCurrentUser();
}
?>

<style>
    .chat-container {
        display: flex;
        height: calc(100vh - 80px);
        background: #0f172a;
        margin-top: 80px;
    }
    
    .chat-sidebar {
        width: 300px;
        background: rgba(15, 23, 42, 0.95);
        border-right: 1px solid #334155;
        display: flex;
        flex-direction: column;
    }
    
    .chat-header {
        padding: 20px;
        border-bottom: 1px solid #334155;
        background: #1e293b;
    }
    
    .chat-header h2 {
        color: white;
        margin: 0;
        font-size: 1.5rem;
    }
    
    .online-users {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }
    
    .user-item {
        display: flex;
        align-items: center;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 8px;
        color: white;
        transition: background 0.2s;
    }
    
    .user-item:hover {
        background: rgba(59, 130, 246, 0.1);
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-weight: bold;
        color: white;
    }
    
    .user-info {
        flex: 1;
    }
    
    .user-name {
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .user-status {
        font-size: 0.8rem;
        color: #10b981;
    }
    
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #0f172a;
    }
    
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: #0f172a;
        border: 1px solid #334155;
    }
    
    .message {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        max-width: 70%;
        margin-bottom: 15px;
    }
    
    .message.own {
        align-self: flex-end;
        flex-direction: row-reverse;
        margin-left: auto;
    }
    
    .message:not(.own) {
        align-self: flex-start;
        margin-right: auto;
    }
    
    .message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .message-content {
        background: #1e293b;
        padding: 12px 16px;
        border-radius: 18px;
        color: white;
        word-wrap: break-word;
        position: relative;
    }
    
    .message.own .message-content {
        background: #3b82f6;
        border-bottom-right-radius: 4px;
    }
    
    .message:not(.own) .message-content {
        background: #1e293b;
        border-bottom-left-radius: 4px;
    }
    
    .message-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    
    .message-sender {
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .message-time {
        font-size: 0.8rem;
        color: #94a3b8;
    }
    
    .message-text {
        line-height: 1.4;
    }
    
    .chat-input-container {
        padding: 20px;
        border-top: 1px solid #334155;
        background: #1e293b;
        flex-shrink: 0;
        border: 2px solid #3b82f6;
    }
    
    .chat-input-form {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .chat-input {
        flex: 1;
        background: #0f172a;
        border: 2px solid #334155;
        border-radius: 25px;
        padding: 12px 20px;
        color: white;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.2s;
        min-height: 20px;
    }
    
    .chat-input:focus {
        border-color: #3b82f6;
    }
    
    .chat-input::placeholder {
        color: #64748b;
    }
    
    .send-button {
        background: #3b82f6;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .send-button:hover {
        background: #2563eb;
    }
    
    .send-button:disabled {
        background: #64748b;
        cursor: not-allowed;
    }
    
    .typing-indicator {
        padding: 10px 20px;
        color: #94a3b8;
        font-style: italic;
        font-size: 0.9rem;
    }
    
    .connection-status {
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 10px 15px;
        border-radius: 8px;
        color: white;
        font-size: 0.9rem;
        z-index: 1000;
        transition: all 0.3s;
    }
    
    .connection-status.connected {
        background: #10b981;
    }
    
    .connection-status.disconnected {
        background: #ef4444;
    }
    
    .connection-status.connecting {
        background: #f59e0b;
    }
    
    .chat-controls {
        padding: 15px 20px;
        border-bottom: 1px solid #334155;
        display: flex;
        justify-content: flex-end;
    }
    
    .clear-chat-btn {
        background: #ef4444;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .clear-chat-btn:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }
    
    .clear-chat-btn:active {
        transform: translateY(0);
    }
    
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 60px);
            margin-top: 60px;
            top: 60px;
        }
        
        .chat-sidebar {
            width: 100%;
            position: absolute;
            left: -100%;
            transition: left 0.3s;
            z-index: 100;
        }
        
        .chat-sidebar.open {
            left: 0;
        }
        
        .message {
            max-width: 85%;
        }
        
        .message.own {
            margin-left: auto;
        }
        
        .message:not(.own) {
            margin-right: auto;
        }
        
        .chat-input-container {
            padding: 15px;
        }
    }
</style>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-header">
            <h2>Community Chat</h2>
        </div>
        <div class="online-users" id="onlineUsers">
            <!-- Online users will be populated here -->
        </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="chat-main">
        <div class="chat-controls">
            <button class="clear-chat-btn" onclick="clearChat()" title="Clear All Messages">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/>
                </svg>
                Clear Chat
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be populated here -->
        </div>
        
        <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <span id="typingText"></span>
        </div>
        
        <div class="chat-input-container">
            <form class="chat-input-form" id="chatForm">
                <input 
                    type="text" 
                    class="chat-input" 
                    id="messageInput" 
                    placeholder="Type your message..." 
                    autocomplete="off"
                    required
                >
                <button type="submit" class="send-button" id="sendButton">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Connection Status -->
<div class="connection-status" id="connectionStatus">Connecting...</div>

<!-- Socket.IO CDN -->
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>

<!-- Pass user data to JavaScript -->
<script>
    window.userData = <?php echo json_encode($currentUser); ?>;
</script>

<script>
class CommunityChat {
    constructor() {
        this.socket = null;
        this.currentUser = this.generateRandomUser();
        this.isTyping = false;
        this.typingTimer = null;
        this.sentMessageIds = new Set(); // Track sent message IDs to prevent duplicates
        
        console.log('User data from server:', window.userData);
        console.log('Generated user:', this.currentUser);
        console.log('Current user ID:', this.currentUser.id);
        
        this.initializeElements();
        this.initializeSocket();
        this.bindEvents();
        this.showWelcomeMessage();
    }
    
    generateRandomUser() {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];
        
        // Use real user data if available, otherwise generate random
        if (window.userData && window.userData.username) {
            return {
                id: 'user_' + window.userData.id,
                name: window.userData.full_name || window.userData.username,
                color: colors[Math.floor(Math.random() * colors.length)]
            };
        } else {
            // Fallback for non-logged-in users
            const names = ['Guest', 'Anonymous', 'Visitor'];
            return {
                id: 'user_' + Math.random().toString(36).substr(2, 9),
                name: names[Math.floor(Math.random() * names.length)],
                color: colors[Math.floor(Math.random() * colors.length)]
            };
        }
    }
    
    initializeElements() {
        this.messagesContainer = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('messageInput');
        this.chatForm = document.getElementById('chatForm');
        this.sendButton = document.getElementById('sendButton');
        this.onlineUsersContainer = document.getElementById('onlineUsers');
        this.typingIndicator = document.getElementById('typingIndicator');
        this.typingText = document.getElementById('typingText');
        this.connectionStatus = document.getElementById('connectionStatus');
        this.chatSidebar = document.getElementById('chatSidebar');
        
        console.log('Elements initialized:', {
            messagesContainer: !!this.messagesContainer,
            messageInput: !!this.messageInput,
            chatForm: !!this.chatForm
        });
    }
    
    initializeSocket() {
        this.updateConnectionStatus('connecting');
        
        // Connect to Socket.IO server
        this.socket = io('http://localhost:3000');
        
        this.socket.on('connect', () => {
            console.log('Connected to server');
            this.updateConnectionStatus('connected');
            this.addSystemMessage('Connected to community chat!');
            
            // Join the chat with user data
            this.socket.emit('user-join', {
                name: this.currentUser.name,
                color: this.currentUser.color
            });
        });
        
        this.socket.on('connect_error', (error) => {
            console.error('Connection error:', error);
            this.updateConnectionStatus('disconnected');
            this.addSystemMessage('Failed to connect to chat server. Using offline mode.');
        });
        
        this.socket.on('disconnect', () => {
            console.log('Disconnected from server');
            this.updateConnectionStatus('disconnected');
            this.addSystemMessage('Disconnected from chat. Trying to reconnect...');
        });
        
        // Handle new messages
        this.socket.on('new-message', (messageData) => {
            console.log('Received new message:', messageData);
            console.log('Message ID:', messageData.id);
            console.log('Sent message IDs:', Array.from(this.sentMessageIds));
            console.log('Is duplicate?', this.sentMessageIds.has(messageData.id));
            
            const isOwn = messageData.senderId === this.currentUser.id;
            console.log('Is own message?', isOwn);
            console.log('Sender ID:', messageData.senderId, 'Current user ID:', this.currentUser.id);
            
            // Check if we already have this message (prevent duplicates)
            if (this.sentMessageIds.has(messageData.id)) {
                console.log('Ignoring duplicate message:', messageData.id);
                return;
            }
            
            // Only add message if it's not from ourselves (to avoid duplicates)
            if (!isOwn) {
                console.log('Adding message from other user');
                this.addMessage(messageData, isOwn);
            } else {
                console.log('Ignoring own message from server');
            }
        });
        
        // Handle message history
        this.socket.on('message-history', (messages) => {
            messages.forEach(message => {
                const isOwn = message.senderId === this.currentUser.id;
                
                // Check if we already have this message (prevent duplicates)
                if (this.sentMessageIds.has(message.id)) {
                    console.log('Ignoring duplicate message in history:', message.id);
                    return;
                }
                
                this.addMessage(message, isOwn);
            });
        });
        
        // Handle user list updates
        this.socket.on('user-list', (users) => {
            this.updateOnlineUsers(users);
        });
        
        // Handle user join/leave notifications
        this.socket.on('user-joined', (data) => {
            this.addSystemMessage(data.message);
        });
        
        this.socket.on('user-left', (data) => {
            this.addSystemMessage(data.message);
        });
        
        // Send user data to server when connecting
        this.socket.emit('user-join', {
            name: this.currentUser.name,
            color: this.currentUser.color,
            userId: this.currentUser.id
        });
        
        // Handle typing indicators
        this.socket.on('user-typing', (data) => {
            this.showTypingIndicator(data.userName, data.isTyping);
        });
        
        // Handle chat cleared event
        this.socket.on('chat-cleared', (data) => {
            const messagesContainer = document.getElementById('chatMessages');
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                
                // Add a system message
                const systemMessage = document.createElement('div');
                systemMessage.className = 'message system';
                systemMessage.style.justifyContent = 'center';
                systemMessage.innerHTML = `
                    <div class="message-content" style="background: #374151; color: #9ca3af; font-style: italic;">
                        ${data.message}
                    </div>
                `;
                messagesContainer.appendChild(systemMessage);
                
                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });
    }
    
    bindEvents() {
        this.chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('Form submitted');
            this.sendMessage();
        });
        
        this.messageInput.addEventListener('input', () => {
            this.handleTyping();
        });
        
        this.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Mobile sidebar toggle
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!this.chatSidebar.contains(e.target) && this.chatSidebar.classList.contains('open')) {
                    this.chatSidebar.classList.remove('open');
                }
            }
        });
    }
    
    sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;
        
        console.log('Attempting to send message:', message);
        console.log('Socket connected:', this.socket ? this.socket.connected : 'No socket');
        
        if (this.socket && this.socket.connected) {
            // Generate unique message ID
            const messageId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            // Add message immediately to show it on the right side
            const messageData = {
                id: messageId,
                text: message,
                sender: this.currentUser.name,
                senderId: this.currentUser.id,
                timestamp: new Date(),
                color: this.currentUser.color
            };
            
            // Track this message ID to prevent duplicates
            this.sentMessageIds.add(messageId);
            console.log('Generated message ID:', messageId);
            console.log('Adding own message immediately:', messageData);
            this.addMessage(messageData, true);
            
            // Send message through socket
            console.log('Sending message via socket');
            this.socket.emit('message', {
                text: message,
                messageId: messageId // Include the ID so server can echo it back
            });
        } else {
            // Fallback for offline mode
            console.log('Using offline mode - socket not connected');
            const messageData = {
                id: Date.now(),
                text: message,
                sender: this.currentUser.name,
                senderId: this.currentUser.id,
                timestamp: new Date(),
                color: this.currentUser.color
            };
            
            console.log('Adding message in offline mode:', messageData);
            this.addMessage(messageData, true);
            this.simulateResponse(message);
        }
        
        this.messageInput.value = '';
        this.stopTyping();
    }
    
    simulateResponse(originalMessage) {
        const responses = [
            "That's interesting!",
            "I agree with that.",
            "Thanks for sharing!",
            "Good point!",
            "I hadn't thought of that.",
            "That makes sense.",
            "I see what you mean.",
            "Absolutely!"
        ];
        
        setTimeout(() => {
            const response = responses[Math.floor(Math.random() * responses.length)];
            const otherUser = this.generateRandomUser();
            
            const messageData = {
                id: Date.now(),
                text: response,
                sender: otherUser.name,
                senderId: otherUser.id,
                timestamp: new Date(),
                color: otherUser.color
            };
            
            this.addMessage(messageData, false);
        }, 1000 + Math.random() * 3000);
    }
    
    addMessage(messageData, isOwn = false) {
        console.log('Adding message to UI:', messageData, 'isOwn:', isOwn);
        console.log('Message CSS class will be:', `message ${isOwn ? 'own' : ''}`);
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOwn ? 'own' : ''}`;
        
        // Handle timestamp - convert to Date if it's a string
        let timestamp;
        if (messageData.timestamp instanceof Date) {
            timestamp = messageData.timestamp;
        } else if (typeof messageData.timestamp === 'string') {
            timestamp = new Date(messageData.timestamp);
        } else {
            timestamp = new Date(); // fallback to current time
        }
        
        const timeString = timestamp.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageElement.innerHTML = `
            <div class="message-avatar" style="background-color: ${messageData.color}">
                ${messageData.sender.charAt(0).toUpperCase()}
            </div>
            <div class="message-content">
                <div class="message-info">
                    <span class="message-sender">${messageData.sender}</span>
                    <span class="message-time">${timeString}</span>
                </div>
                <div class="message-text">${this.escapeHtml(messageData.text)}</div>
            </div>
        `;
        
        this.messagesContainer.appendChild(messageElement);
        this.scrollToBottom();
        console.log('Message added to DOM with class:', messageElement.className);
    }
    
    addSystemMessage(text) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message system';
        messageElement.style.justifyContent = 'center';
        messageElement.innerHTML = `
            <div class="message-content" style="background: #374151; color: #9ca3af; font-style: italic;">
                ${text}
            </div>
        `;
        
        this.messagesContainer.appendChild(messageElement);
        this.scrollToBottom();
    }
    
    showWelcomeMessage() {
        if (window.userData && window.userData.username) {
            this.addSystemMessage(`Welcome to the community chat, ${this.currentUser.name}!`);
            this.addSystemMessage('Connected to real-time chat server!');
        } else {
            this.addSystemMessage(`Welcome to the community chat, ${this.currentUser.name}!`);
            this.addSystemMessage('You are chatting as a guest. Sign in to use your real name.');
        }
    }
    
    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            if (this.socket && this.socket.connected) {
                this.socket.emit('typing', true);
            }
        }
        
        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.stopTyping();
        }, 1000);
    }
    
    stopTyping() {
        if (this.isTyping) {
            this.isTyping = false;
            if (this.socket && this.socket.connected) {
                this.socket.emit('typing', false);
            }
        }
    }
    
    updateConnectionStatus(status) {
        this.connectionStatus.className = `connection-status ${status}`;
        
        switch(status) {
            case 'connected':
                this.connectionStatus.textContent = 'Connected';
                break;
            case 'disconnected':
                this.connectionStatus.textContent = 'Disconnected';
                break;
            case 'connecting':
                this.connectionStatus.textContent = 'Connecting...';
                break;
        }
    }
    
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    updateOnlineUsers(users) {
        this.onlineUsersContainer.innerHTML = '';
        
        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'user-item';
            userElement.innerHTML = `
                <div class="user-avatar" style="background-color: ${user.color}">
                    ${user.name.charAt(0).toUpperCase()}
                </div>
                <div class="user-info">
                    <div class="user-name">${user.name}</div>
                    <div class="user-status">Online</div>
                </div>
            `;
            this.onlineUsersContainer.appendChild(userElement);
        });
    }
    
    showTypingIndicator(userName, isTyping) {
        if (isTyping) {
            this.typingText.textContent = `${userName} is typing...`;
            this.typingIndicator.style.display = 'block';
        } else {
            this.typingIndicator.style.display = 'none';
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat when page loads
// Global function to clear chat
function clearChat() {
    if (confirm('Are you sure you want to clear all messages? This action cannot be undone.')) {
        // Notify server to clear chat for all users
        if (window.chatInstance && window.chatInstance.socket && window.chatInstance.socket.connected) {
            window.chatInstance.socket.emit('clear-chat');
        }
        
        // Clear local messages immediately
        const messagesContainer = document.getElementById('chatMessages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
            
            // Add a system message
            const systemMessage = document.createElement('div');
            systemMessage.className = 'message system';
            systemMessage.style.justifyContent = 'center';
            systemMessage.innerHTML = `
                <div class="message-content" style="background: #374151; color: #9ca3af; font-style: italic;">
                    Chat cleared
                </div>
            `;
            messagesContainer.appendChild(systemMessage);
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatInstance = new CommunityChat();
});

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('chatSidebar');
    sidebar.classList.toggle('open');
}
</script>

