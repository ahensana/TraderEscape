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
    /* Modern Chat Container */
    .chat-container {
        display: flex;
        height: calc(100vh - 80px);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin-top: 80px;
        border-radius: 20px 20px 0 0;
        overflow: hidden;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.3);
    }
    
    /* Sidebar */
    .chat-sidebar {
        width: 320px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    }
    
    .chat-header {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
    }
    
    .chat-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    /* Main Chat Area */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
    }
    
    .chat-controls {
        padding: 15px 20px;
        background: rgba(255, 255, 255, 0.8);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chat-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .chat-actions {
        display: flex;
        gap: 10px;
    }
    
    .action-btn {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        color: #495057;
    }
    
    .action-btn:hover {
        background: #e9ecef;
        transform: translateY(-1px);
    }
    
    .clear-chat-btn {
        background: #ff6b6b;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .clear-chat-btn:hover {
        background: #ff5252;
        transform: translateY(-1px);
    }
    
    /* Messages Area */
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    }
    
    .message {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        max-width: 70%;
        margin-bottom: 20px;
        animation: messageSlideIn 0.3s ease-out;
    }
    
    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 3px solid rgba(255, 255, 255, 0.3);
    }
    
    .message-content {
        background: white;
        padding: 12px 16px;
        border-radius: 20px;
        color: #333;
        word-wrap: break-word;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-width: 100%;
    }
    
    .message.own .message-content {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 6px;
    }
    
    .message:not(.own) .message-content {
        background: white;
        color: #333;
        border-bottom-left-radius: 6px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .message-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    
    .message-sender {
        font-weight: 600;
        font-size: 0.85rem;
        opacity: 0.8;
    }
    
    .message-time {
        font-size: 0.75rem;
        opacity: 0.6;
    }
    
    .message-text {
        font-size: 0.95rem;
        line-height: 1.4;
        word-break: break-word;
    }
    
    .message-text .emoji {
        font-size: 1.2em;
    }
    
    /* Input Area */
    .chat-input-container {
        padding: 20px;
        background: rgba(255, 255, 255, 0.95);
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(20px);
    }
    
    .chat-form {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        background: white;
        border-radius: 25px;
        padding: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .input-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .emoji-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s;
        color: #666;
    }
    
    .emoji-btn:hover {
        background: #f8f9fa;
        transform: scale(1.1);
    }
    
    .message-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 12px 16px;
        font-size: 0.95rem;
        background: transparent;
        color: #333;
        resize: none;
        min-height: 20px;
        max-height: 120px;
        font-family: inherit;
    }
    
    .message-input::placeholder {
        color: #999;
    }
    
    .send-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .send-button:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .send-button:active {
        transform: scale(0.95);
    }
    
    .send-button:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* Online Users */
    .online-users {
        padding: 20px;
        flex: 1;
        overflow-y: auto;
    }
    
    .online-user {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .online-user:hover {
        background: rgba(102, 126, 234, 0.1);
        transform: translateX(5px);
    }
    
    .online-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        color: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .online-user-info {
        flex: 1;
    }
    
    .online-user-name {
        color: #333;
        font-size: 0.9rem;
        font-weight: 500;
        margin: 0;
    }
    
    .online-user-status {
        color: #666;
        font-size: 0.8rem;
        margin: 0;
    }
    
    .online-user-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
    }
    
    /* Typing Indicator */
    .typing-indicator {
        padding: 8px 20px;
        color: #666;
        font-style: italic;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .typing-dots {
        display: flex;
        gap: 4px;
    }
    
    .typing-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #666;
        animation: typingDot 1.4s infinite ease-in-out;
    }
    
    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes typingDot {
        0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
    }
    
    /* Emoji Picker */
    .emoji-picker {
        position: absolute;
        bottom: 80px;
        right: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 15px;
        display: none;
        z-index: 1000;
        max-width: 300px;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .emoji-picker.show {
        display: block;
        animation: emojiPickerSlide 0.3s ease-out;
    }
    
    @keyframes emojiPickerSlide {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .emoji-item {
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        text-align: center;
        font-size: 1.2rem;
        transition: all 0.2s;
    }
    
    .emoji-item:hover {
        background: #f8f9fa;
        transform: scale(1.2);
    }
    
    /* System Messages */
    .message.system {
        align-self: center;
        margin: 10px 0;
    }
    
    .message.system .message-content {
        background: rgba(0, 0, 0, 0.1);
        color: #666;
        font-style: italic;
        font-size: 0.9rem;
        padding: 8px 16px;
        border-radius: 20px;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 60px);
            margin-top: 60px;
            border-radius: 0;
        }
        
        .chat-sidebar {
            width: 100%;
            position: absolute;
            left: -100%;
            transition: left 0.3s;
            z-index: 1000;
            height: 100%;
        }
        
        .chat-sidebar.open {
            left: 0;
        }
        
        .chat-messages {
            padding: 15px;
        }
        
        .message {
            max-width: 85%;
        }
        
        .chat-input-container {
            padding: 15px;
        }
        
        .message-input {
            font-size: 16px;
        }
        
        .emoji-picker {
            right: 10px;
            bottom: 70px;
            max-width: 280px;
        }
    }
    
    /* Scrollbar Styling */
    .chat-messages::-webkit-scrollbar,
    .online-users::-webkit-scrollbar {
        width: 6px;
    }
    
    .chat-messages::-webkit-scrollbar-track,
    .online-users::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
    }
    
    .chat-messages::-webkit-scrollbar-thumb,
    .online-users::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.3);
        border-radius: 3px;
    }
    
    .chat-messages::-webkit-scrollbar-thumb:hover,
    .online-users::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.5);
    }
</style>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-header">
            <h2>💬 Community Chat</h2>
        </div>
        <div class="online-users" id="onlineUsers">
            <!-- Online users will be populated here -->
        </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="chat-main">
        <div class="chat-controls">
            <h3 class="chat-title">💬 Community Chat</h3>
            <div class="chat-actions">
                <button class="action-btn" onclick="toggleSidebar()" title="Toggle Users">
                    <span>👥</span>
                    Users
                </button>
                <button class="clear-chat-btn" onclick="clearChat()" title="Clear All Messages">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/>
                    </svg>
                    Clear
                </button>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be populated here -->
        </div>
        
        <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <span id="typingText"></span>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <form class="chat-form" id="chatForm">
                <div class="input-wrapper">
                    <button type="button" class="emoji-btn" onclick="toggleEmojiPicker()" title="Add Emoji">
                        😊
                    </button>
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type your message here..."
                        rows="1"
                    ></textarea>
                </div>
                <button type="submit" class="send-button" id="sendButton" title="Send Message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22,2 15,22 11,13 2,9 22,2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Emoji Picker -->
<div class="emoji-picker" id="emojiPicker">
    <div class="emoji-grid" id="emojiGrid">
        <!-- Emojis will be populated here -->
    </div>
</div>

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
        this.sentMessageIds = new Set();
        this.hasJoined = false;
        this.emojiPickerVisible = false;
        
        console.log('User data from server:', window.userData);
        console.log('Generated user:', this.currentUser);
        console.log('Current user ID:', this.currentUser.id);
        console.log('User ID type:', typeof this.currentUser.id);
        
        this.initializeElements();
        this.initializeSocket();
        this.bindEvents();
        this.showWelcomeMessage();
        this.initializeEmojiPicker();
    }
    
    generateRandomUser() {
        const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'];
        
        if (window.userData && window.userData.username) {
            return {
                id: 'user_' + window.userData.id,
                name: window.userData.full_name || window.userData.username,
                color: colors[Math.floor(Math.random() * colors.length)]
            };
        } else {
            let guestId = localStorage.getItem('chat_guest_id');
            if (!guestId) {
                guestId = 'guest_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('chat_guest_id', guestId);
            }
            
            const names = ['Guest', 'Anonymous', 'Visitor'];
            return {
                id: guestId,
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
        this.chatSidebar = document.getElementById('chatSidebar');
        this.emojiPicker = document.getElementById('emojiPicker');
        this.emojiGrid = document.getElementById('emojiGrid');
        
        console.log('Elements initialized:', {
            messagesContainer: !!this.messagesContainer,
            messageInput: !!this.messageInput,
            chatForm: !!this.chatForm
        });
    }
    
    initializeSocket() {
        this.socket = io('http://localhost:3000');
        
        this.socket.on('connect', () => {
            console.log('Connected to server');
            
            if (!this.hasJoined) {
                this.socket.emit('user-join', {
                    name: this.currentUser.name,
                    color: this.currentUser.color,
                    userId: this.currentUser.id
                });
                this.hasJoined = true;
                console.log('Sent user-join event');
            } else {
                console.log('Already joined, skipping user-join event');
            }
        });
        
        this.socket.on('connect_error', (error) => {
            console.error('Connection error:', error);
            this.addSystemMessage('Failed to connect to chat server. Using offline mode.');
        });
        
        this.socket.on('disconnect', () => {
            console.log('Disconnected from server');
            this.addSystemMessage('Disconnected from chat. Trying to reconnect...');
        });
        
        this.socket.on('new-message', (messageData) => {
            console.log('Received new message:', messageData);
            console.log('Message ID:', messageData.id);
            console.log('Sent message IDs:', Array.from(this.sentMessageIds));
            console.log('Is duplicate?', this.sentMessageIds.has(messageData.id));
            
            const isOwn = messageData.senderId === this.currentUser.id;
            console.log('Is own message?', isOwn);
            console.log('Sender ID:', messageData.senderId, 'Current user ID:', this.currentUser.id);
            
            if (this.sentMessageIds.has(messageData.id)) {
                console.log('Ignoring duplicate message:', messageData.id);
                return;
            }
            
            if (!isOwn) {
                console.log('Adding message from other user');
                this.addMessage(messageData, isOwn);
            } else {
                console.log('Ignoring own message from server');
            }
        });
        
        this.socket.on('message-history', (messages) => {
            console.log('Received message history:', messages.length, 'messages');
            console.log('Current user ID:', this.currentUser.id);
            
            messages.forEach(message => {
                const isOwn = message.senderId === this.currentUser.id;
                console.log('History message from:', message.sender, 'isOwn:', isOwn);
                
                if (this.sentMessageIds.has(message.id)) {
                    console.log('Ignoring duplicate message in history:', message.id);
                    return;
                }
                
                this.addMessage(message, isOwn);
            });
        });
        
        this.socket.on('user-list', (users) => {
            console.log('Received user list:', users);
            this.updateOnlineUsers(users);
        });
        
        this.socket.on('user-joined', (data) => {
            this.addSystemMessage(data.message);
        });
        
        this.socket.on('user-left', (data) => {
            this.addSystemMessage(data.message);
        });
        
        this.socket.on('user-typing', (data) => {
            this.showTypingIndicator(data.userName, data.isTyping);
        });
        
        this.socket.on('chat-cleared', (data) => {
            const messagesContainer = document.getElementById('chatMessages');
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                
                const systemMessage = document.createElement('div');
                systemMessage.className = 'message system';
                systemMessage.style.justifyContent = 'center';
                systemMessage.innerHTML = `
                    <div class="message-content" style="background: rgba(0, 0, 0, 0.1); color: #666; font-style: italic;">
                        ${data.message}
                    </div>
                `;
                messagesContainer.appendChild(systemMessage);
                
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });
    }
    
    bindEvents() {
        this.chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        this.messageInput.addEventListener('input', () => {
            this.handleTyping();
            this.autoResize();
        });
        
        this.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.emojiPicker.contains(e.target) && !e.target.closest('.emoji-btn')) {
                hideEmojiPicker();
            }
        });
    }
    
    initializeEmojiPicker() {
        const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'];
        
        this.emojiGrid.innerHTML = emojis.map(emoji => 
            `<div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>`
        ).join('');
    }
    
    sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;
        
        console.log('Attempting to send message:', message);
        console.log('Socket connected:', this.socket ? this.socket.connected : 'No socket');
        
        if (this.socket && this.socket.connected) {
            const messageId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            const messageData = {
                id: messageId,
                text: message,
                sender: this.currentUser.name,
                senderId: this.currentUser.id,
                timestamp: new Date(),
                color: this.currentUser.color
            };
            
            this.sentMessageIds.add(messageId);
            console.log('Generated message ID:', messageId);
            console.log('Adding own message immediately:', messageData);
            this.addMessage(messageData, true);
            
            console.log('Sending message via socket');
            this.socket.emit('message', {
                text: message,
                messageId: messageId
            });
        } else {
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
        this.autoResize();
        this.stopTyping();
    }
    
    addMessage(messageData, isOwn = false) {
        console.log('Adding message to UI:', messageData, 'isOwn:', isOwn);
        console.log('Message CSS class will be:', `message ${isOwn ? 'own' : ''}`);
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOwn ? 'own' : ''}`;
        
        let timestamp;
        if (messageData.timestamp instanceof Date) {
            timestamp = messageData.timestamp;
        } else if (typeof messageData.timestamp === 'string') {
            timestamp = new Date(messageData.timestamp);
        } else {
            timestamp = new Date();
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
            <div class="message-content">
                ${text}
            </div>
        `;
        
        this.messagesContainer.appendChild(messageElement);
        this.scrollToBottom();
    }
    
    showWelcomeMessage() {
        if (window.userData && window.userData.username) {
            this.addSystemMessage(`Welcome to the community chat, ${this.currentUser.name}!`);
        } else {
            this.addSystemMessage(`Welcome to the community chat, ${this.currentUser.name}!`);
            this.addSystemMessage('You are chatting as a guest. Sign in to use your real name.');
        }
    }
    
    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.socket.emit('typing', true);
        }
        
        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.stopTyping();
        }, 1000);
    }
    
    stopTyping() {
        if (this.isTyping) {
            this.isTyping = false;
            this.socket.emit('typing', false);
        }
    }
    
    showTypingIndicator(userName, isTyping) {
        if (isTyping) {
            this.typingText.textContent = `${userName} is typing`;
            this.typingIndicator.style.display = 'flex';
        } else {
            this.typingIndicator.style.display = 'none';
        }
    }
    
    updateOnlineUsers(users) {
        this.onlineUsersContainer.innerHTML = '';
        
        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'online-user';
            userElement.innerHTML = `
                <div class="online-user-avatar" style="background-color: ${user.color}">
                    ${user.name.charAt(0).toUpperCase()}
                </div>
                <div class="online-user-info">
                    <p class="online-user-name">${user.name}</p>
                    <p class="online-user-status">Online</p>
                </div>
                <div class="online-user-indicator"></div>
            `;
            this.onlineUsersContainer.appendChild(userElement);
        });
    }
    
    autoResize() {
        this.messageInput.style.height = 'auto';
        this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
    }
    
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    simulateResponse(message) {
        setTimeout(() => {
            const responses = [
                "That's interesting!",
                "I agree with you.",
                "Tell me more about that.",
                "Thanks for sharing!",
                "I see what you mean.",
                "That's a good point.",
                "I hadn't thought of that.",
                "That makes sense."
            ];
            
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            const messageData = {
                id: Date.now(),
                text: randomResponse,
                sender: 'Bot',
                senderId: 'bot',
                timestamp: new Date(),
                color: '#10b981'
            };
            
            this.addMessage(messageData, false);
        }, 1000 + Math.random() * 3000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global functions
function clearChat() {
    if (confirm('Are you sure you want to clear all messages? This action cannot be undone.')) {
        if (window.chatInstance && window.chatInstance.socket && window.chatInstance.socket.connected) {
            window.chatInstance.socket.emit('clear-chat');
        }
        
        const messagesContainer = document.getElementById('chatMessages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
            
            const systemMessage = document.createElement('div');
            systemMessage.className = 'message system';
            systemMessage.style.justifyContent = 'center';
            systemMessage.innerHTML = `
                <div class="message-content" style="background: rgba(0, 0, 0, 0.1); color: #666; font-style: italic;">
                    Chat cleared
                </div>
            `;
            messagesContainer.appendChild(systemMessage);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('chatSidebar');
    sidebar.classList.toggle('open');
}

function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    if (picker.classList.contains('show')) {
        picker.classList.remove('show');
    } else {
        picker.classList.add('show');
    }
}

function hideEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.classList.remove('show');
}

function insertEmoji(emoji) {
    const input = document.getElementById('messageInput');
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;
    
    input.value = text.substring(0, start) + emoji + text.substring(end);
    input.focus();
    input.setSelectionRange(start + emoji.length, start + emoji.length);
    
    hideEmojiPicker();
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatInstance = new CommunityChat();
});
</script>

<?php include 'includes/footer.php'; ?>