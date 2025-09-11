<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // User is not logged in, show login popup instead of chat
    $showLoginPopup = true;
    $currentUser = null;
} else {
    // User is logged in, get their information
    $currentUser = getCurrentUser();
    $showLoginPopup = false;
}
?>

<style>
    /* Modern Chat Container */
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .chat-container {
        display: flex;
        height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 0;
        border-radius: 0;
        overflow: hidden;
        box-shadow: none;
    }
    
    /* Sidebar */
    .chat-sidebar {
        width: 320px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(37, 99, 235, 0.2);
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 20px rgba(0, 0, 0, 0.3);
    }
    
    .chat-header {
        padding: 20px;
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        text-align: center;
        min-height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
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
        padding: 10px 20px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(37, 99, 235, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 20px;
    }
    
    .chat-title-container {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .chat-logo {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: -15px;
    }
    
    .chat-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #ffffff;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .chat-actions {
        display: flex;
        gap: 10px;
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
        /* Hide scroll indicators */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    .chat-messages::-webkit-scrollbar {
        display: none; /* WebKit browsers (Chrome, Safari, Edge) */
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
    
    .text-message-container {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 4px;
        gap: 8px;
    }
    
    .message-text {
        font-size: 0.95rem;
        line-height: 1.4;
        word-break: break-word;
        flex: 1;
        margin: 0;
    }
    
    .text-time {
        font-size: 0.75rem;
        color: #999;
        flex-shrink: 0;
        white-space: nowrap;
    }
    
    .message.own .text-time {
        color: #ffffff;
    }
    
    .message-text .emoji {
        font-size: 1.2em;
    }
    
    /* Message Context Menu */
    .message-context-menu {
        position: absolute;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(0, 0, 0, 0.1);
        padding: 8px;
        z-index: 1000;
        display: none;
        min-width: 150px;
        backdrop-filter: blur(10px);
    }
    
    .message-context-menu.show {
        display: block;
        animation: contextMenuSlide 0.2s ease-out;
    }
    
    @keyframes contextMenuSlide {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .context-menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #333;
        font-size: 0.9rem;
    }
    
    .context-menu-item:hover {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }
    
    .context-menu-item i {
        font-size: 1rem;
        width: 16px;
        text-align: center;
    }
    
    .emoji-reactions {
        display: flex;
        gap: 4px;
        margin-top: 4px;
        flex-wrap: wrap;
    }
    
    .emoji-reaction {
        background: rgba(37, 99, 235, 0.1);
        border: 1px solid rgba(37, 99, 235, 0.2);
        border-radius: 12px;
        padding: 2px 6px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .emoji-reaction:hover {
        background: rgba(37, 99, 235, 0.2);
        transform: scale(1.1);
    }
    
    .reply-indicator {
        background: rgba(37, 99, 235, 0.1);
        border-left: 3px solid #2563eb;
        padding: 8px 12px;
        margin: 8px 0;
        border-radius: 0 8px 8px 0;
        font-size: 0.85rem;
        color: #2563eb;
    }
    
    .reply-indicator .reply-to {
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .reply-indicator .reply-text {
        color: #666;
        font-style: italic;
    }
    
    /* Input Area */
    .chat-input-container {
        padding: -1px;
        background: white;
        
        backdrop-filter: blur(20px);
        width: 100%;
        box-sizing: border-box;
    }
    
    .chat-form {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        background: white;
        border-radius: 25px;
        padding: 3px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(37, 99, 235, 0.3);
        width: 98%;
        box-sizing: border-box;
        margin-left: 10px;
    }
    
    .input-wrapper {
        flex: 1;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
    }
    
    .emoji-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .emoji-btn:hover {
        background: #f8f9fa;
        transform: scale(1.1);
    }
    
    .emoji-icon {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }
    
    .attach-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .attach-btn:hover {
        background: #f8f9fa;
        transform: scale(1.1);
    }
    
    .attach-icon {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }
    
    .file-previews-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-left: 8px;
        max-width: 300px;
        flex-shrink: 0;
    }
    
    .file-preview {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 6px 10px;
        display: inline-flex;
        align-items: center;
        max-width: 180px;
        flex-shrink: 0;
    }
    
    .file-preview-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    
    .file-info {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }
    
    .file-icon {
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .file-name {
        font-weight: 500;
        color: #333;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .file-size {
        color: #666;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .remove-file-btn {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-left: 6px;
    }
    
    .remove-file-btn:hover {
        background: #c82333;
    }
    
    .image-caption {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
        align-items: flex-end;
        gap: 10px;
    }
    
    .caption-text {
        flex: 1;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .own-caption-text {
        color: #ffffff;
    }
    
    .other-caption-text {
        color: #333333;
    }
    
    .caption-time {
        color: #999;
        font-size: 0.75rem;
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .message.own .caption-time {
        color: #ffffff;
    }
    
    .own-message-text {
        color: #ffffff;
    }
    
    .other-message-text {
        color: #333333;
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
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: pre-wrap;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
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
        /* Hide scroll indicators */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    .online-users::-webkit-scrollbar {
        display: none; /* WebKit browsers (Chrome, Safari, Edge) */
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
        background: rgba(37, 99, 235, 0.1);
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
        color: #ffffff;
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
        left: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 15px;
        display: none;
        z-index: 1000;
        max-width: 420px;
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
        gap: 6px;
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
        /* Hide scroll indicators */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    .emoji-grid::-webkit-scrollbar {
        display: none; /* WebKit browsers (Chrome, Safari, Edge) */
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
        body {
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container {
            height: 100vh;
            margin: 0;
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
            left: 20px;
            bottom: 70px;
            max-width: 350px;
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
        background: rgba(37, 99, 235, 0.3);
        border-radius: 3px;
    }
    
    .chat-messages::-webkit-scrollbar-thumb:hover,
    .online-users::-webkit-scrollbar-thumb:hover {
        background: rgba(37, 99, 235, 0.5);
    }
    
    /* Login Popup Styles */
    .login-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        backdrop-filter: blur(10px);
    }
    
    .login-popup {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        border-radius: 20px;
        text-align: center;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: popupSlideIn 0.3s ease-out;
    }
    
    @keyframes popupSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .login-popup h2 {
        color: white;
        margin: 0 0 20px 0;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .login-popup p {
        color: rgba(255, 255, 255, 0.9);
        margin: 0 0 30px 0;
        font-size: 1.1rem;
        line-height: 1.6;
    }
    
    .login-popup .chat-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        display: block;
    }
    
    .login-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .login-btn {
        padding: 15px 30px;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        min-width: 150px;
    }
    
    .login-btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
    }
    
    .login-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 172, 254, 0.6);
    }
    
    .login-btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .login-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }
    
    .login-popup .close-btn {
        position: absolute;
        top: 15px;
        right: 20px;
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .login-popup .close-btn:hover {
        color: white;
    }
    
    /* Mobile responsive for login popup */
    @media (max-width: 768px) {
        .login-popup {
            padding: 30px 20px;
            margin: 20px;
        }
        
        .login-popup h2 {
            font-size: 1.5rem;
        }
        
        .login-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .login-btn {
            width: 100%;
            max-width: 250px;
        }
    }
</style>

<?php if ($showLoginPopup): ?>
<!-- Login Required Popup -->
<div class="login-popup-overlay" id="loginPopup">
    <div class="login-popup">
        <button class="close-btn" onclick="closeLoginPopup()">&times;</button>
        <span class="chat-icon">💬</span>
        <h2>Join the Community Chat</h2>
        <p>To access our community chat, you need to sign in to your account. Connect with other traders, share insights, and be part of our trading community!</p>
        <div class="login-buttons">
            <a href="login.php" class="login-btn login-btn-primary">Sign In</a>
            <a href="account.php" class="login-btn login-btn-secondary">Create Account</a>
        </div>
    </div>
</div>

<script>
function closeLoginPopup() {
    // Redirect to home page when popup is closed
    window.location.href = 'index.php';
}
</script>
<?php else: ?>

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
            <div class="chat-title-container">
                <img src="assets/logo.png" alt="Trader's Escape Logo" class="chat-logo">
                <h3 class="chat-title">The Trader's Escape</h3>
            </div>
            <div class="chat-actions">
                <!-- Navigation buttons removed -->
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be populated here -->
        </div>
        
        <!-- Context Menu -->
        <div class="message-context-menu" id="messageContextMenu">
            <div class="context-menu-item" onclick="reactToMessage('👍')">
                <i class="bi bi-hand-thumbs-up"></i>
                <span>👍 Like</span>
            </div>
            <div class="context-menu-item" onclick="reactToMessage('❤️')">
                <i class="bi bi-heart"></i>
                <span>❤️ Love</span>
            </div>
            <div class="context-menu-item" onclick="reactToMessage('😂')">
                <i class="bi bi-emoji-laughing"></i>
                <span>😂 Laugh</span>
            </div>
            <div class="context-menu-item" onclick="reactToMessage('😮')">
                <i class="bi bi-emoji-surprised"></i>
                <span>😮 Wow</span>
            </div>
            <div class="context-menu-item" onclick="replyToMessage()">
                <i class="bi bi-reply"></i>
                <span>Reply</span>
            </div>
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
                        <img src="assets/emoji.png" alt="Emoji" class="emoji-icon">
                    </button>
                    <button type="button" class="attach-btn" onclick="attachFile()" title="Attach File">
                        <img src="assets/attach-file.png" alt="Attach File" class="attach-icon">
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
        this.restoreReplyData();
    }
    
    generateRandomUser() {
        const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'];
        
        // Generate device info
        const deviceInfo = this.getDeviceInfo();
        
        if (window.userData && window.userData.username) {
            const user = {
                id: 'user_' + window.userData.id,
                name: window.userData.full_name || window.userData.username,
                color: colors[Math.floor(Math.random() * colors.length)],
                deviceInfo: deviceInfo
            };
            // Store user data in localStorage for persistence across refreshes
            localStorage.setItem('chat_current_user', JSON.stringify(user));
            return user;
        } else {
            // Try to restore from localStorage first
            const storedUser = localStorage.getItem('chat_current_user');
            if (storedUser) {
                try {
                    const user = JSON.parse(storedUser);
                    console.log('Restored user from localStorage:', user);
                    return user;
                } catch (e) {
                    console.log('Failed to parse stored user, creating new one');
                }
            }
            
            let guestId = localStorage.getItem('chat_guest_id');
            if (!guestId) {
                guestId = 'guest_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('chat_guest_id', guestId);
            }
            
            const names = ['Guest', 'Anonymous', 'Visitor'];
            const user = {
                id: guestId,
                name: names[Math.floor(Math.random() * names.length)] + '_' + deviceInfo.deviceType,
                color: colors[Math.floor(Math.random() * colors.length)],
                deviceInfo: deviceInfo
            };
            
            // Store guest user data in localStorage for persistence
            localStorage.setItem('chat_current_user', JSON.stringify(user));
            return user;
        }
    }
    
    getDeviceInfo() {
        const userAgent = navigator.userAgent;
        let deviceType = 'Desktop';
        let browser = 'Unknown';
        
        // Detect device type
        if (/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
            deviceType = 'Mobile';
        } else if (/Tablet|iPad/i.test(userAgent)) {
            deviceType = 'Tablet';
        }
        
        // Detect browser
        if (userAgent.includes('Chrome')) browser = 'Chrome';
        else if (userAgent.includes('Firefox')) browser = 'Firefox';
        else if (userAgent.includes('Safari')) browser = 'Safari';
        else if (userAgent.includes('Edge')) browser = 'Edge';
        
        return {
            deviceType: deviceType,
            browser: browser,
            userAgent: userAgent.substring(0, 50) + '...',
            timestamp: new Date().toISOString()
        };
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
        // Try to connect to the same host as the current page, but on port 3000
        const currentHost = window.location.hostname;
        const socketUrl = currentHost === 'localhost' || currentHost === '127.0.0.1' 
            ? 'http://localhost:3000' 
            : `http://${currentHost}:3000`;
        
        console.log('Connecting to socket server:', socketUrl);
        this.socket = io(socketUrl, {
            transports: ['websocket', 'polling'],
            withCredentials: true,
        });
        
        this.socket.on('connect', () => {
            console.log('Connected to server');
            console.log('hasJoined flag:', this.hasJoined);
            console.log('Current user:', this.currentUser);
            
            if (!this.hasJoined) {
                const joinData = {
                    name: this.currentUser.name,
                    color: this.currentUser.color,
                    userId: this.currentUser.id,
                    deviceInfo: this.currentUser.deviceInfo
                };
                console.log('Sending user-join event with data:', joinData);
                this.socket.emit('user-join', joinData);
                this.hasJoined = true;
                console.log('User-join event sent, hasJoined set to true');
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
            console.log('Message reply data:', messageData.replyTo);
            console.log('Message reply ID:', messageData.replyToId);
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
                console.log('Adding message from other user with reply data:', messageData.replyTo);
                this.addMessage(messageData, isOwn);
            } else {
                console.log('Ignoring own message from server');
            }
        });
        
        this.socket.on('message-history', (messages) => {
            console.log('Received message history:', messages.length, 'messages');
            console.log('Current user ID:', this.currentUser.id);
            console.log('Current user name:', this.currentUser.name);
            
            messages.forEach(message => {
                // Check ownership by both ID and name for better accuracy
                const isOwnById = message.senderId === this.currentUser.id;
                const isOwnByName = message.sender === this.currentUser.name;
                const isOwn = isOwnById || isOwnByName;
                
                console.log('History message from:', message.sender, 'senderId:', message.senderId, 'isOwn:', isOwn);
                console.log('Message reply data:', message.replyTo);
                
                // Store reply data in localStorage if it exists
                if (message.replyTo) {
                    this.storeReplyData(message.id, message.replyTo);
                }
                
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

        // React Chat Event Listeners - Additional support for React frontend
        this.socket.on('new_message', (messageData) => {
            console.log('Received React message:', messageData);
            const isOwn = messageData.username === this.currentUser.name;
            
            if (!isOwn) {
                const message = {
                    id: messageData.id,
                    text: messageData.content,
                    sender: messageData.username,
                    senderId: messageData.username,
                    timestamp: new Date(messageData.timestamp),
                    color: '#3b82f6',
                    file: messageData.file
                };
                this.addMessage(message, false);
            }
        });

        this.socket.on('previous_messages', (messages) => {
            console.log('Received React message history:', messages.length, 'messages');
            console.log('Current user name:', this.currentUser.name);
            messages.forEach(message => {
                // Check ownership by name for React messages
                const isOwn = message.username === this.currentUser.name;
                console.log('React history message from:', message.username, 'isOwn:', isOwn);
                
                const messageData = {
                    id: message.id,
                    text: message.content,
                    sender: message.username,
                    senderId: message.username,
                    timestamp: new Date(message.timestamp),
                    color: '#3b82f6',
                    file: message.file
                };
                this.addMessage(messageData, isOwn);
            });
        });

        this.socket.on('users_update', (users) => {
            console.log('Received React user list:', users);
            const formattedUsers = users.map(user => ({
                id: user.userId,
                name: user.username,
                color: '#3b82f6',
                deviceInfo: {
                    deviceType: 'Unknown',
                    browser: 'Unknown'
                }
            }));
            this.updateOnlineUsers(formattedUsers);
        });

        this.socket.on('user_stop_typing', (data) => {
            this.showTypingIndicator(data.username, false);
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
        const emojis = [
            '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾',
            '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👋', '🤚', '🖐️', '✋', '🖖', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁️', '👅', '👄', '💋', '🩸',
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️', '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠', 'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🈳', '🈂️', '🛂', '🛃', '🛄', '🛅', '🚹', '🚺', '🚼', '🚻', '🚮', '🎦', '📶', '🈁', '🔣', 'ℹ️', '🔤', '🔡', '🔠', '🆖', '🆗', '🆙', '🆒', '🆕', '🆓', '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'
        ];
        
        this.emojiGrid.innerHTML = emojis.map(emoji => 
            `<div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>`
        ).join('');
    }
    
    async sendMessage() {
        const message = this.messageInput.value.trim();
        const hasFiles = window.selectedFiles && window.selectedFiles.length > 0;
        
        if (!message && !hasFiles) return;
        
        console.log('Attempting to send message:', message, 'with files:', hasFiles);
        console.log('Socket connected:', this.socket ? this.socket.connected : 'No socket');
        
        // If there are files, upload them first
        let fileDataArray = [];
        if (hasFiles) {
            try {
                // Upload all files
                for (const file of window.selectedFiles) {
                    const fileData = await this.uploadFile(file);
                    fileDataArray.push(fileData);
                }
            } catch (error) {
                console.error('File upload failed:', error);
                alert('Failed to upload files. Please try again.');
                return;
            }
        }
        
        if (this.socket && this.socket.connected) {
            const messageId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            // Get reply data if replying to a message
            let replyData = null;
            if (replyToMessageId) {
                replyData = this.getReplyData(replyToMessageId);
                // If we can't get it from DOM, try localStorage
                if (!replyData) {
                    replyData = this.getStoredReplyData(replyToMessageId);
                }
                console.log('Sending reply data to server:', replyData);
            }
            
            const messageData = {
                id: messageId,
                text: message || (hasFiles && !fileDataArray.some(file => file.mimetype && file.mimetype.startsWith('image/')) ? `📎 ${fileDataArray.length} file${fileDataArray.length > 1 ? 's' : ''}` : ''),
                sender: this.currentUser.name,
                senderId: this.currentUser.id,
                timestamp: new Date(),
                color: this.currentUser.color,
                files: fileDataArray,
                replyTo: replyData,
                replyToId: replyToMessageId || null
            };
            
            this.sentMessageIds.add(messageId);
            console.log('Generated message ID:', messageId);
            console.log('Adding own message immediately:', messageData);
            this.addMessage(messageData, true);
            
            console.log('Sending message via socket with reply data:', messageData.replyTo);
            // Send only in PHP format
            this.socket.emit('message', {
                text: messageData.text,
                messageId: messageId,
                files: fileDataArray,
                replyTo: messageData.replyTo,
                replyToId: messageData.replyToId
            });
        } else {
            console.log('Using offline mode - socket not connected');
            const messageData = {
                id: Date.now(),
                text: message || (hasFiles && !fileDataArray.some(file => file.mimetype && file.mimetype.startsWith('image/')) ? `📎 ${fileDataArray.length} file${fileDataArray.length > 1 ? 's' : ''}` : ''),
                sender: this.currentUser.name,
                senderId: this.currentUser.id,
                timestamp: new Date(),
                color: this.currentUser.color,
                files: fileDataArray,
                replyTo: replyToMessageId ? this.getReplyData(replyToMessageId) : null,
                replyToId: replyToMessageId || null
            };
            
            console.log('Adding message in offline mode:', messageData);
            this.addMessage(messageData, true);
            this.simulateResponse(messageData.text);
        }
        
        this.messageInput.value = '';
        this.autoResize();
        this.stopTyping();
        
        // Clear file selection and preview
        if (hasFiles) {
            removeFilePreview();
        }
        
        // Clear reply indicator after sending
        if (replyToMessageId) {
            cancelReply();
        }
    }
    
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await fetch('http://localhost:3000/api/upload', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Upload failed');
        }
        
        return await response.json();
    }
    
    addMessage(messageData, isOwn = false) {
        console.log('Adding message to UI:', messageData, 'isOwn:', isOwn);
        console.log('Current user:', this.currentUser);
        console.log('Message sender:', messageData.sender, 'Current user name:', this.currentUser.name);
        console.log('Message CSS class will be:', `message ${isOwn ? 'own' : ''}`);
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOwn ? 'own' : ''}`;
        messageElement.setAttribute('data-message-id', messageData.id);
        
        let timestamp;
        if (messageData.timestamp instanceof Date) {
            timestamp = messageData.timestamp;
        } else if (typeof messageData.timestamp === 'string') {
            timestamp = new Date(messageData.timestamp);
        } else {
            timestamp = new Date();
        }
        
        const timeString = timestamp.toLocaleTimeString([], {hour: 'numeric', minute:'2-digit', hour12: true});
        
        // Check for stored reply data - prioritize messageData.replyTo, then localStorage
        let replyData = messageData.replyTo;
        console.log('addMessage - Initial reply data from messageData.replyTo:', replyData);
        console.log('addMessage - messageData.replyToId:', messageData.replyToId);
        
        // If no reply data in message but we have a replyToId, try to get from localStorage
        if (!replyData && messageData.replyToId) {
            replyData = this.getStoredReplyData(messageData.replyToId);
            console.log('Restored reply data from localStorage for replyToId:', messageData.replyToId, replyData);
        }
        
        // If we have reply data, ensure it's stored for future use
        if (replyData) {
            // Store under the current message ID (the reply message)
            this.storeReplyData(messageData.id, replyData);
            console.log('Stored reply data for message ID:', messageData.id, 'with data:', replyData);
        }
        
        console.log('addMessage - Final reply data to be rendered:', replyData);
        
        messageElement.innerHTML = `
            ${!isOwn ? `<div class="message-avatar" style="background-color: ${messageData.color}">
                ${messageData.sender.charAt(0).toUpperCase()}
            </div>` : ''}
            <div class="message-content">
                ${replyData ? `
                    <div class="reply-preview" style="background: rgba(37, 99, 235, 0.1); border-left: 3px solid #2563eb; padding: 8px 12px; margin-bottom: 8px; border-radius: 0 8px 8px 0; font-size: 0.85rem;">
                        <div style="color: #ffffff; font-weight: 600; margin-bottom: 2px;">${replyData.sender}</div>
                        <div style="color: #ffffff; font-style: italic;">${replyData.text.substring(0, 50)}${replyData.text.length > 50 ? '...' : ''}</div>
                    </div>
                ` : ''}
                ${messageData.text && !(messageData.files && messageData.files.length > 0 && messageData.files.some(file => file.mimetype && file.mimetype.startsWith('image/'))) ? `
                    <div class="message-info">
                        ${!isOwn ? `<span class="message-sender">${messageData.sender}</span>` : ''}
                    </div>
                    <div class="text-message-container">
                        <div class="message-text ${isOwn ? 'own-message-text' : 'other-message-text'}">${this.escapeHtml(messageData.text)}</div>
                        <div class="text-time">${timeString}</div>
                    </div>
                ` : `
                    <div class="message-info">
                        ${!isOwn ? `<span class="message-sender">${messageData.sender}</span>` : ''}
                    </div>
                `}
                ${messageData.files && messageData.files.length > 0 ? this.renderFileAttachments(messageData.files) : ''}
                ${messageData.files && messageData.files.length > 0 && messageData.files.some(file => file.mimetype && file.mimetype.startsWith('image/')) ? `
                    <div class="image-caption">
                        ${messageData.text ? `<div class="caption-text ${isOwn ? 'own-caption-text' : 'other-caption-text'}">${this.escapeHtml(messageData.text)}</div>` : ''}
                        <div class="caption-time">${timeString}</div>
                    </div>
                ` : ''}
            </div>
        `;
        
        // Add right-click event listener for non-own messages
        if (!isOwn) {
            messageElement.addEventListener('contextmenu', (e) => {
                showContextMenu(e, messageData.id, messageData);
            });
        }
        
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
        // Always show welcome message without guest warning
        this.addSystemMessage(`Welcome to the community chat, ${this.currentUser.name}!`);
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
                </div>
                <div class="online-user-indicator"></div>
            `;
            this.onlineUsersContainer.appendChild(userElement);
        });
    }
    
    autoResize() {
        this.messageInput.style.height = 'auto';
        this.messageInput.style.width = '100%';
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
    
    renderFileAttachments(files) {
        if (!files || files.length === 0) return '';
        
        let html = '<div class="file-attachments-container mt-2">';
        
        files.forEach(file => {
            if (file.mimetype && file.mimetype.startsWith('image/')) {
                html += `<div class="file-attachment mb-2">
                    <img src="http://localhost:3000${file.url}" alt="${file.originalName}" 
                         class="max-w-full rounded-lg cursor-pointer" 
                         onclick="window.open('http://localhost:3000${file.url}', '_blank')"
                         style="max-height: 200px; object-fit: cover;">
                </div>`;
            } else {
                html += `<div class="file-attachment mb-2">
                    <a href="http://localhost:3000${file.url}" target="_blank" 
                       class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        ${file.originalName}
                    </a>
                </div>`;
            }
        });
        
        html += '</div>';
        return html;
    }
    
    renderFileAttachment(file) {
        // Legacy function for single file - now uses multiple file function
        return this.renderFileAttachments([file]);
    }
    
    getReplyData(messageId) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            const senderElement = messageElement.querySelector('.message-sender');
            const textElement = messageElement.querySelector('.message-text');
            
            if (senderElement && textElement) {
                const replyData = {
                    sender: senderElement.textContent,
                    text: textElement.textContent
                };
                
                console.log('Getting reply data for message ID:', messageId, 'Data:', replyData);
                
                // Store reply data in localStorage for persistence
                this.storeReplyData(messageId, replyData);
                
                return replyData;
            }
        }
        
        // Try to get from localStorage if DOM element not found
        const storedData = this.getStoredReplyData(messageId);
        console.log('Fallback to localStorage data for ID:', messageId, storedData);
        return storedData;
    }
    
    storeReplyData(messageId, replyData) {
        const storedReplies = JSON.parse(localStorage.getItem('chat_replies') || '{}');
        storedReplies[messageId] = replyData;
        localStorage.setItem('chat_replies', JSON.stringify(storedReplies));
        console.log('Stored reply data for message ID:', messageId, replyData);
    }
    
    getStoredReplyData(messageId) {
        const storedReplies = JSON.parse(localStorage.getItem('chat_replies') || '{}');
        console.log('Looking for reply data for message ID:', messageId);
        console.log('Available stored replies:', Object.keys(storedReplies));
        console.log('All stored reply data:', storedReplies);
        console.log('Found reply data:', storedReplies[messageId]);
        return storedReplies[messageId] || null;
    }
    
    restoreReplyData() {
        // This function will be called after messages are loaded to restore reply data
        console.log('Restoring reply data for existing messages...');
        
        // Check multiple times to ensure we catch all messages
        const checkAndRestore = () => {
            const messages = document.querySelectorAll('[data-message-id]');
            console.log('Found', messages.length, 'messages to check for reply data');
            
            messages.forEach(messageElement => {
                const messageId = messageElement.getAttribute('data-message-id');
                const replyPreview = messageElement.querySelector('.reply-preview');
                
                // If message has no reply preview but should have one, try to restore it
                if (!replyPreview && messageId) {
                    const storedReplyData = this.getStoredReplyData(messageId);
                    if (storedReplyData) {
                        console.log('Restoring reply preview for message:', messageId);
                        this.addReplyPreview(messageElement, storedReplyData);
                    }
                }
            });
        };
        
        // Check immediately and then again after delays
        checkAndRestore();
        setTimeout(checkAndRestore, 1000);
        setTimeout(checkAndRestore, 3000);
        setTimeout(checkAndRestore, 5000);
    }
    
    addReplyPreview(messageElement, replyData) {
        const messageContent = messageElement.querySelector('.message-content');
        if (messageContent) {
            const replyPreview = document.createElement('div');
            replyPreview.className = 'reply-preview';
            replyPreview.style.cssText = `
                background: rgba(37, 99, 235, 0.1); 
                border-left: 3px solid #2563eb; 
                padding: 8px 12px; 
                margin-bottom: 8px; 
                border-radius: 0 8px 8px 0; 
                font-size: 0.85rem;
            `;
            replyPreview.innerHTML = `
                <div style="color: #ffffff; font-weight: 600; margin-bottom: 2px;">${replyData.sender}</div>
                <div style="color: #ffffff; font-style: italic;">${replyData.text.substring(0, 50)}${replyData.text.length > 50 ? '...' : ''}</div>
            `;
            
            // Insert at the beginning of message content
            messageContent.insertBefore(replyPreview, messageContent.firstChild);
        }
    }
}

// Global functions

let currentContextMessage = null;
let replyToMessageId = null;

function showContextMenu(event, messageId, messageData) {
    event.preventDefault();
    event.stopPropagation();
    
    const contextMenu = document.getElementById('messageContextMenu');
    const rect = event.target.getBoundingClientRect();
    
    // Don't show context menu for own messages
    if (messageData.senderId === window.chatInstance.currentUser.id) {
        return;
    }
    
    currentContextMessage = { id: messageId, data: messageData };
    
    // Position the context menu
    contextMenu.style.left = event.clientX + 'px';
    contextMenu.style.top = event.clientY + 'px';
    contextMenu.classList.add('show');
    
    // Hide context menu when clicking elsewhere
    setTimeout(() => {
        document.addEventListener('click', hideContextMenu);
    }, 100);
}

function hideContextMenu() {
    const contextMenu = document.getElementById('messageContextMenu');
    contextMenu.classList.remove('show');
    document.removeEventListener('click', hideContextMenu);
}

function reactToMessage(emoji) {
    if (!currentContextMessage) return;
    
    const messageId = currentContextMessage.id;
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    
    if (messageElement) {
        let reactionsContainer = messageElement.querySelector('.emoji-reactions');
        if (!reactionsContainer) {
            reactionsContainer = document.createElement('div');
            reactionsContainer.className = 'emoji-reactions';
            messageElement.querySelector('.message-content').appendChild(reactionsContainer);
        }
        
        // Check if emoji already exists
        const existingReaction = reactionsContainer.querySelector(`[data-emoji="${emoji}"]`);
        if (existingReaction) {
            // Increment count
            const count = parseInt(existingReaction.dataset.count) + 1;
            existingReaction.dataset.count = count;
            existingReaction.innerHTML = `${emoji} ${count}`;
        } else {
            // Add new reaction
            const reactionElement = document.createElement('div');
            reactionElement.className = 'emoji-reaction';
            reactionElement.dataset.emoji = emoji;
            reactionElement.dataset.count = '1';
            reactionElement.innerHTML = `${emoji} 1`;
            reactionsContainer.appendChild(reactionElement);
        }
    }
    
    hideContextMenu();
}

function replyToMessage() {
    if (!currentContextMessage) return;
    
    replyToMessageId = currentContextMessage.id;
    const messageData = currentContextMessage.data;
    
    // Store the reply data immediately using the chat instance method
    const replyData = {
        sender: messageData.sender,
        text: messageData.text
    };
    
    if (window.chatInstance) {
        // Store under the original message ID (the one being replied to)
        window.chatInstance.storeReplyData(replyToMessageId, replyData);
        console.log('Stored reply data for original message ID:', replyToMessageId, 'with data:', replyData);
    }
    
    // Show reply indicator in input area
    showReplyIndicator(messageData);
    
    // Focus on message input
    const messageInput = document.getElementById('messageInput');
    messageInput.focus();
    
    hideContextMenu();
}

function showReplyIndicator(messageData) {
    console.log('showReplyIndicator called with:', messageData);
    
    // Remove existing reply indicator
    const existingIndicator = document.querySelector('.reply-indicator');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    // Create new reply indicator with context menu style
    const replyIndicator = document.createElement('div');
    replyIndicator.className = 'reply-indicator';
    replyIndicator.style.cssText = `
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.1);
        padding: 12px;
        margin: 8px 20px;
        position: relative;
        display: block;
        z-index: 100;
    `;
    
    replyIndicator.innerHTML = `
        <div style="color: #2563eb; font-weight: 600; margin-bottom: 4px; font-size: 0.9rem;">${messageData.sender}</div>
        <div style="color: #333; font-size: 0.85rem; margin-bottom: 8px;">${messageData.text.substring(0, 50)}${messageData.text.length > 50 ? '...' : ''}</div>
        <div style="color: #666; font-size: 0.8rem; margin-bottom: 8px;">Replying to this message</div>
        <button onclick="cancelReply()" style="position: absolute; right: 8px; top: 8px; background: none; border: none; color: #666; cursor: pointer; font-size: 1.2rem; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(0,0,0,0.1)'" onmouseout="this.style.background='none'">×</button>
    `;
    
    // Insert before the chat form
    const chatForm = document.querySelector('.chat-form');
    console.log('Chat form found:', chatForm);
    console.log('Chat form parent:', chatForm ? chatForm.parentNode : 'Not found');
    
    if (chatForm && chatForm.parentNode) {
        chatForm.parentNode.insertBefore(replyIndicator, chatForm);
        console.log('Reply indicator inserted');
    } else {
        console.error('Could not find chat form or its parent');
        // Fallback: try to insert into chat input container
        const chatInputContainer = document.querySelector('.chat-input-container');
        if (chatInputContainer) {
            chatInputContainer.insertBefore(replyIndicator, chatInputContainer.firstChild);
            console.log('Reply indicator inserted into chat input container as fallback');
        }
    }
}

function cancelReply() {
    replyToMessageId = null;
    const replyIndicator = document.querySelector('.reply-indicator');
    if (replyIndicator) {
        replyIndicator.remove();
    }
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

function attachFile() {
    // Create a file input element
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.accept = 'image/*,.pdf,.doc,.docx,.txt,.zip,.rar';
    fileInput.style.display = 'none';
    
    // Add event listener for file selection
    fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length > 0) {
            // Check file sizes (max 10MB each)
            const maxSize = 10 * 1024 * 1024; // 10MB
            const oversizedFiles = files.filter(file => file.size > maxSize);
            
            if (oversizedFiles.length > 0) {
                alert(`Some files are too large. Maximum size is 10MB per file.`);
                return;
            }
            
            // Store the selected files for sending
            window.selectedFiles = files;
            
            // Show file previews in input area
            showFilePreviews(files);
        }
    });
    
    // Trigger file selection
    document.body.appendChild(fileInput);
    fileInput.click();
    document.body.removeChild(fileInput);
}

function showFilePreviews(files) {
    // Remove any existing file previews
    const existingPreviews = document.querySelectorAll('.file-preview');
    existingPreviews.forEach(preview => preview.remove());
    
    // Create file previews container
    const previewsContainer = document.createElement('div');
    previewsContainer.id = 'filePreviewsContainer';
    previewsContainer.className = 'file-previews-container';
    
    files.forEach((file, index) => {
        const preview = document.createElement('div');
        preview.className = 'file-preview';
        preview.innerHTML = `
            <div class="file-preview-content">
                <div class="file-info">
                    <span class="file-icon">📎</span>
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">(${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
                <button type="button" class="remove-file-btn" onclick="removeFilePreview(${index})" title="Remove file">×</button>
            </div>
        `;
        previewsContainer.appendChild(preview);
    });
    
    // Insert inside the input wrapper, after the attach button
    const inputWrapper = document.querySelector('.input-wrapper');
    const attachButton = inputWrapper.querySelector('.attach-btn');
    attachButton.parentNode.insertBefore(previewsContainer, attachButton.nextSibling);
}

function showFilePreview(file) {
    // Legacy function for single file - now uses multiple file function
    showFilePreviews([file]);
}

function removeFilePreview(index = null) {
    if (index !== null) {
        // Remove specific file by index
        if (window.selectedFiles && window.selectedFiles.length > index) {
            window.selectedFiles.splice(index, 1);
            
            // Update previews
            if (window.selectedFiles.length > 0) {
                showFilePreviews(window.selectedFiles);
            } else {
                // Remove all previews if no files left
                const existingPreviews = document.querySelectorAll('.file-preview, .file-previews-container');
                existingPreviews.forEach(preview => preview.remove());
                window.selectedFiles = null;
            }
        }
    } else {
        // Remove all files (legacy function)
        const existingPreviews = document.querySelectorAll('.file-preview, .file-previews-container');
        existingPreviews.forEach(preview => preview.remove());
        window.selectedFiles = null;
        window.selectedFile = null;
    }
}


function addLoadingMessage(text) {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) {
        console.error('Messages container not found');
        return null;
    }
    
    const loadingMessage = document.createElement('div');
    loadingMessage.className = 'message system';
    loadingMessage.style.justifyContent = 'center';
    loadingMessage.innerHTML = `
        <div class="message-content" style="background: rgba(0, 0, 0, 0.1); color: #666; font-style: italic;">
            ${text}
        </div>
    `;
    messagesContainer.appendChild(loadingMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    return loadingMessage;
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatInstance = new CommunityChat();
    
    // Add a manual trigger for reply restoration (for debugging)
    window.restoreReplies = () => {
        if (window.chatInstance) {
            window.chatInstance.restoreReplyData();
        }
    };
    
    // Add a function to clear reply data (for debugging)
    window.clearReplyData = () => {
        localStorage.removeItem('chat_replies');
        console.log('Cleared all reply data from localStorage');
    };
    
    // Add a function to show current reply data (for debugging)
    window.showReplyData = () => {
        const storedReplies = JSON.parse(localStorage.getItem('chat_replies') || '{}');
        console.log('Current reply data in localStorage:', storedReplies);
        return storedReplies;
    };
});
</script>

<?php endif; ?>


