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
        position: relative;
    }
    
    .chat-container.sidebar-closed {
        justify-content: center;
        padding: 0;
    }
    
    /* New Independent Sidebar */
    .new-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 300px;
        height: 100vh;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(37, 99, 235, 0.2);
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 20px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        z-index: 1000;
        transform: translateX(-100%);
        will-change: transform;
    }
    
    .new-sidebar.open {
        transform: translateX(0);
    }
    
    /* Chat container adjustment when sidebar is open */
    .chat-container.sidebar-open {
        margin-left: 300px;
        transition: margin-left 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    @media (max-width: 768px) {
        .chat-container.sidebar-open {
            margin-left: 280px;
        }
    }
    
    @media (max-width: 480px) {
        .chat-container.sidebar-open {
            margin-left: 0;
        }
    }
    
    /* Sidebar Header */
    .new-sidebar-header {
        padding: 20px;
        border-bottom: 1px solid rgba(37, 99, 235, 0.2);
        display: flex;
        align-items: center;
        background: transparent;
    }
    
    .new-sidebar-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .new-sidebar-icon {
        width: 28px;
        height: 28px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }
    
    .new-sidebar-title h2 {
        color: white;
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }
    
    .new-sidebar-close-btn {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .new-sidebar-close-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    /* Search Bar */
    .new-sidebar-search {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(37, 99, 235, 0.1);
    }
    
    .new-search-container {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .new-search-icon {
        position: absolute;
        left: 12px;
        color: rgba(255, 255, 255, 0.5);
        z-index: 1;
    }
    
    .new-search-input {
        width: 100%;
        padding: 10px 12px 10px 40px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: white;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.2s ease;
    }
    
    .new-search-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }
    
    .new-search-input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(37, 99, 235, 0.5);
    }
    
    /* Quick Actions */
    .new-sidebar-actions {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(37, 99, 235, 0.1);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .new-action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        text-align: left;
    }
    
    .new-action-btn:hover {
        background: rgba(37, 99, 235, 0.2);
        color: white;
    }
    
    .new-action-btn.active {
        background: rgba(37, 99, 235, 0.3);
        color: white;
    }
    
    /* Sidebar Sections */
    .new-sidebar-section {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .new-section-header {
        padding: 16px 20px 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(37, 99, 235, 0.1);
    }
    
    .new-section-header h3 {
        color: rgba(255, 255, 255, 0.9);
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .new-online-count {
        background: rgba(37, 99, 235, 0.3);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    /* Online Users */
    .new-online-users {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }
    
    .new-user-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    
    .new-user-item:hover {
        background: rgba(37, 99, 235, 0.1);
        border-left-color: rgba(37, 99, 235, 0.5);
    }
    
    .new-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        position: relative;
    }
    
    .new-user-status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid rgba(15, 23, 42, 0.95);
    }
    
    .new-user-status.online {
        background: #10b981;
    }
    
    .new-user-status.away {
        background: #f59e0b;
    }
    
    .new-user-status.offline {
        background: #6b7280;
    }
    
    .new-user-info {
        flex: 1;
        min-width: 0;
    }
    
    .new-user-name {
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .new-user-last-seen {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
        margin: 0;
    }
    
    /* Sidebar Footer */
    .new-sidebar-footer {
        padding: 16px 20px;
        border-top: 1px solid rgba(37, 99, 235, 0.2);
        background: transparent;
    }
    
    .new-settings-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 10px 12px;
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        text-align: left;
    }
    
    .new-settings-btn:hover {
        background: rgba(37, 99, 235, 0.2);
        color: white;
    }
    
    /* Sidebar Toggle Button */
    .new-sidebar-toggle-btn {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: white;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        will-change: transform, background-color;
    }
    
    .new-sidebar-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }
    
    .new-sidebar-toggle-btn:active {
        transform: scale(0.95);
    }
    
    .new-sidebar-toggle-btn svg {
        width: 20px;
        height: 20px;
        transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .new-sidebar {
            width: 280px;
        }
    }
    
    @media (max-width: 480px) {
        .new-sidebar {
            width: 100vw;
        }
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
        width: 100%;
        border-radius: 20px;
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
        align-items: flex-end;
        gap: 8px;
        max-width: 70%;
        margin-bottom: 8px;
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
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.8);
    }
    
    .message-content {
        background: #ffffff;
        padding: 8px 12px;
        border-radius: 18px 18px 18px 4px;
        color: #303030;
        word-wrap: break-word;
        position: relative;
        box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13);
        max-width: 100%;
    }
    
    .message.own .message-content {
        background: linear-gradient(135deg, #a5b4fc 0%, #c4b5fd 100%);
        color: #000000;
        border-radius: 18px 18px 4px 18px;
    }
    
    .message:not(.own) .message-content {
        background: #ffffff;
        color: #303030;
        border-radius: 18px 18px 18px 4px;
    }
    
    .message-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    
    .message-sender {
        font-weight: 600;
        font-size: 0.8rem;
        color: #000000;
        margin-bottom: 2px;
    }
    
    .message-time {
        font-size: 0.7rem;
        color: #999;
        margin-top: 4px;
        text-align: right;
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
        outline: none;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        pointer-events: none;
    }
    
    .text-time {
        font-size: 0.75rem;
        color: #999;
        flex-shrink: 0;
        white-space: nowrap;
    }
    
    .message.own .text-time {
        color: #000000;
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
        outline: none;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
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
    
    .caption-sender {
        font-weight: 600;
        font-size: 0.8rem;
        color: #000000;
        margin-bottom: 2px;
    }
    
    .caption-text {
        flex: 1;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .own-caption-text {
        color: #000000;
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
        color: #000000;
    }
    
    .own-message-text {
        color: #000000;
    }
    
    .other-message-text {
        color: #000000;
    }
    
    /* Image Grid Layouts */
    .image-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        margin-bottom: 8px;
        max-width: 300px;
    }
    
    .grid-image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .grid-image {
        display: block;
        transition: transform 0.2s ease;
    }
    
    .grid-image:hover {
        transform: scale(1.02);
    }
    
    /* Document Attachment Styles */
    .document-attachment {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        margin-bottom: 8px;
        max-width: 300px;
    }
    
    .document-icon {
        flex-shrink: 0;
    }
    
    .pdf-icon, .word-icon, .excel-icon, .generic-icon {
        width: 40px;
        height: 48px;
        position: relative;
    }
    
    .pdf-icon-body, .word-icon-body, .excel-icon-body, .generic-icon-body {
        width: 100%;
        height: 100%;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        position: relative;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .pdf-icon-corner, .word-icon-corner, .excel-icon-corner, .generic-icon-corner {
        position: absolute;
        top: 0;
        right: 0;
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-top: 8px solid #ddd;
    }
    
    .pdf-text, .word-text, .excel-text, .generic-text {
        position: absolute;
        bottom: 2px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 8px;
        font-weight: bold;
        color: #333;
    }
    
    .pdf-icon-body {
        background: #ff4444;
    }
    
    .pdf-text {
        color: white;
    }
    
    .word-icon-body {
        background: #2b579a;
    }
    
    .word-text {
        color: white;
    }
    
    .excel-icon-body {
        background: #217346;
    }
    
    .excel-text {
        color: white;
    }
    
    .document-info {
        flex: 1;
        min-width: 0;
    }
    
    .document-name {
        font-weight: 600;
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
        word-break: break-word;
    }
    
    .document-details {
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .document-actions {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .open-btn {
        background: #007bff;
        color: white;
    }
    
    .open-btn:hover {
        background: #0056b3;
    }
    
    
    .document-caption {
        margin-top: 8px;
        text-align: right;
    }
    
    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        border-radius: 8px;
    }
    
    /* Single image */
    .image-grid.single-image {
        max-width: 300px;
    }
    
    .image-grid.single-image .grid-image-item {
        width: 100%;
        height: 200px;
    }
    
    /* Two images */
    .image-grid.two-images {
        max-width: 300px;
    }
    
    .image-grid.two-images .grid-image-item {
        width: calc(50% - 1px);
        height: 150px;
    }
    
    /* Three images */
    .image-grid.three-images {
        max-width: 300px;
    }
    
    .image-grid.three-images .grid-image-item {
        width: calc(33.333% - 2px);
        height: 100px;
    }
    
    /* Four images */
    .image-grid.four-images {
        max-width: 300px;
    }
    
    .image-grid.four-images .grid-image-item {
        width: calc(50% - 1px);
        height: 75px;
    }
    
    /* Five images */
    .image-grid.five-images {
        max-width: 300px;
    }
    
    .image-grid.five-images .grid-image-item {
        width: calc(33.333% - 2px);
        height: 60px;
    }
    
    .image-grid.five-images .grid-image-item:nth-child(4),
    .image-grid.five-images .grid-image-item:nth-child(5) {
        width: calc(50% - 1px);
    }
    
    /* Six or more images */
    .image-grid.six-plus-images {
        max-width: 300px;
    }
    
    .image-grid.six-plus-images .grid-image-item {
        width: calc(33.333% - 2px);
        height: 50px;
    }
    
    .image-grid.six-plus-images .grid-image-item:nth-child(4),
    .image-grid.six-plus-images .grid-image-item:nth-child(5) {
        width: calc(50% - 1px);
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
        <img src="assets/bubble-chat.png" alt="Chat Icon" class="chat-icon" style="width: 80px; height: 80px; object-fit: contain; filter: brightness(0) invert(1); display: block; margin: 0 auto 20px auto;">
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

<!-- New Independent Sidebar -->
<div class="new-sidebar" id="newSidebar">
    <!-- Sidebar Header -->
    <div class="new-sidebar-header">
        <div class="new-sidebar-title">
            <img src="assets/bubble-chat.png" alt="Chat Icon" class="new-sidebar-icon">
            <h2>Community Chat</h2>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="new-sidebar-search">
        <div class="new-search-container">
            <svg class="new-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" placeholder="Search messages..." class="new-search-input" id="newSearchInput">
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="new-sidebar-actions">
        <button class="new-action-btn" onclick="showAllMessages()" title="All Messages">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>All Messages</span>
        </button>
            <button class="new-action-btn" onclick="showMediaMessages()" title="Media">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21,15 16,10 5,21"></polyline>
            </svg>
            <span>Media</span>
        </button>
    </div>

    <!-- Online Users Section -->
    <div class="new-sidebar-section">
        <div class="new-section-header">
            <h3>Online Users</h3>
            <span class="new-online-count" id="newOnlineCount">0</span>
        </div>
        <div class="new-online-users" id="newOnlineUsers">
            <!-- Online users will be populated here -->
        </div>
    </div>

    <!-- Chat Settings -->
    <div class="new-sidebar-footer">
        <button class="new-settings-btn" onclick="showChatSettings()" title="Settings">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span>Settings</span>
        </button>
    </div>
</div>


<div class="chat-container">
    
    <!-- Main Chat Area -->
    <div class="chat-main">
        <div class="chat-controls">
            <div class="chat-title-container">
                <button class="new-sidebar-toggle-btn" id="newSidebarToggleBtn" onclick="toggleNewSidebar()" title="Toggle Sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
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
            <div class="context-menu-item" onclick="reactToMessage('🤑')">
                <i class="bi bi-emoji-smile"></i>
                <span>🤑 Money</span>
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
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        data-form-type="other"
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
        
        // Clear any leftover reply state from previous sessions
        if (typeof replyToMessageId !== 'undefined') {
            replyToMessageId = null;
        }
        if (typeof currentContextMessage !== 'undefined') {
            currentContextMessage = null;
        }
        
        // Also clear any reply indicators that might be left over
        const existingReplyIndicator = document.querySelector('.reply-indicator');
        if (existingReplyIndicator) {
            existingReplyIndicator.remove();
        }
        
        // Initialize new sidebar
        this.initializeNewSidebar();
        
        console.log('Cleared reply state on page load - replyToMessageId:', replyToMessageId);
        
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
    
    initializeNewSidebar() {
        // Initialize search functionality
        initializeNewSearch();
        
        // Set default active button
        const allMessagesBtn = document.querySelector('.new-action-btn[onclick="showAllMessages()"]');
        if (allMessagesBtn) {
            allMessagesBtn.classList.add('active');
        }
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
            console.log('Updated online users with', users.length, 'users');
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
        
        // Prevent context menu in chat input area
        this.messageInput.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });
        
        // Prevent context menu in chat form area
        this.chatForm.addEventListener('contextmenu', (e) => {
            e.preventDefault();
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
            
            // Debug: Check if we're accidentally replying to something
            console.log('=== SEND MESSAGE DEBUG ===');
            console.log('replyToMessageId:', replyToMessageId);
            console.log('Message text:', message);
            console.log('Is this a reply?', !!replyToMessageId);
            
            // Prevent self-replies by clearing replyToMessageId if it's invalid
            if (replyToMessageId && !document.querySelector(`[data-message-id="${replyToMessageId}"]`)) {
                console.log('WARNING: replyToMessageId points to non-existent message, clearing it');
                replyToMessageId = null;
            }
            
            // Get reply data if replying to a message
            let replyData = null;
            if (replyToMessageId) {
                console.log('Getting reply data for messageId:', replyToMessageId);
                
                // First try to get from localStorage (this should work for individual image replies)
                replyData = this.getStoredReplyData(replyToMessageId);
                console.log('localStorage result:', replyData);
                
                // If not found in localStorage, try getReplyData (for regular message replies)
                if (!replyData) {
                    replyData = this.getReplyData(replyToMessageId);
                    console.log('getReplyData result:', replyData);
                }
                
                // If still not found, try the global variable (fallback for individual image replies)
                if (!replyData && window.currentReplyData) {
                    replyData = window.currentReplyData;
                    console.log('Using global reply data:', replyData);
                }
                
                console.log('Sending reply data to server:', replyData);
            } else {
                console.log('This is a normal message (not a reply)');
            }
            
            const messageData = {
                id: messageId,
                text: message || '',
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
            
            // Clear the global reply data after sending
            if (replyToMessageId) {
                window.currentReplyData = null;
                console.log('Cleared global reply data after sending');
            }
            
            console.log('Sending message via socket with reply data:', messageData.replyTo);
            console.log('Full messageData being sent:', messageData);
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
                text: message || '',
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
        messageElement.setAttribute('data-sender-name', messageData.sender);
        
        // Add reply data attributes if this message is a reply
        if (messageData.replyTo) {
            messageElement.setAttribute('data-reply-to', messageData.replyTo);
        }
        if (messageData.replyToId) {
            messageElement.setAttribute('data-reply-to-id', messageData.replyToId);
        }
        
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
        if (replyData && messageData.replyToId) {
            // Store under the original message ID (the message being replied to)
            this.storeReplyData(messageData.replyToId, replyData);
            console.log('Stored reply data for original message ID:', messageData.replyToId, 'with data:', replyData);
        }
        
        console.log('addMessage - Final reply data to be rendered:', replyData);
        
        messageElement.innerHTML = `
            ${!isOwn ? `<div class="message-avatar" style="background-color: ${messageData.color}">
                ${messageData.sender.charAt(0).toUpperCase()}
            </div>` : ''}
            <div class="message-content">
                ${replyData ? `
                    ${!isOwn ? `<div style="color: #000000; font-weight: 600; margin-bottom: 4px; font-size: 0.9rem;">${messageData.sender}</div>` : ''}
                    <div class="reply-preview" style="background: rgba(37, 99, 235, 0.1); border-left: 3px solid #2563eb; padding: 8px 12px; margin-bottom: 8px; border-radius: 0 8px 8px 0; font-size: 0.85rem;">
                        <div style="color: #000000; font-weight: 600; margin-bottom: 2px;">${replyData.sender}</div>
                        ${replyData.imageUrl ? `
                            <img src="${replyData.imageUrl}" alt="Reply image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                        ` : `
                            <div style="color: #000000; font-style: italic;">${replyData.text.substring(0, 50)}${replyData.text.length > 50 ? '...' : ''}</div>
                        `}
                    </div>
                ` : ''}
                ${messageData.text && !(messageData.files && messageData.files.length > 0 && messageData.files.some(file => file.mimetype && file.mimetype.startsWith('image/'))) ? `
                    <div class="message-info">
                        ${!isOwn && !replyData ? `<span class="message-sender">${messageData.sender}</span>` : ''}
                    </div>
                    <div class="text-message-container">
                        <div class="message-text ${isOwn ? 'own-message-text' : 'other-message-text'}">${this.escapeHtml(messageData.text)}</div>
                        <div class="text-time">${timeString}</div>
                    </div>
                ` : `
                    <div class="message-info">
                        ${!isOwn && !replyData ? `<span class="message-sender">${messageData.sender}</span>` : ''}
                    </div>
                `}
                ${messageData.files && messageData.files.length > 0 ? this.renderFileAttachments(messageData.files) : ''}
                ${messageData.files && messageData.files.length > 0 && messageData.files.some(file => file.mimetype && file.mimetype.startsWith('image/')) ? `
                    <div class="image-caption">
                        ${messageData.text ? `<div class="caption-text ${isOwn ? 'own-caption-text' : 'other-caption-text'}">${this.escapeHtml(messageData.text)}</div>` : ''}
                        <div class="caption-time">${timeString}</div>
                    </div>
                ` : ''}
                ${messageData.files && messageData.files.length > 0 && messageData.files.some(file => !file.mimetype || !file.mimetype.startsWith('image/')) && !messageData.files.some(file => file.mimetype && file.mimetype.startsWith('image/')) ? `
                    <div class="document-caption">
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
        
        // Restore reactions for this message
        setTimeout(() => {
            restoreReactions(messageData.id);
        }, 100);
        
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
        // Update the new sidebar online users
        updateNewUserList(users);
        
        // Also update the old sidebar if it exists
        if (this.onlineUsersContainer) {
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
    
    renderFileAttachments(files, messageId = null) {
        if (!files || files.length === 0) return '';
        
        // Separate images from other files
        const imageFiles = files.filter(file => file.mimetype && file.mimetype.startsWith('image/'));
        const otherFiles = files.filter(file => !file.mimetype || !file.mimetype.startsWith('image/'));
        
        let html = '';
        
        // Render images in grid layout
        if (imageFiles.length > 0) {
            html += this.renderImageGrid(imageFiles, messageId);
        }
        
        // Render other files in document format
        if (otherFiles.length > 0) {
            html += '<div class="file-attachments-container">';
            otherFiles.forEach(file => {
                const fileType = this.getFileType(file.originalName, file.mimetype);
                const fileSize = this.formatFileSize(file.size);
                
                html += `<div class="document-attachment">
                    <div class="document-icon">
                        ${this.getFileIcon(fileType)}
                    </div>
                    <div class="document-info">
                        <div class="document-name">${file.originalName}</div>
                        <div class="document-details">${fileSize}, ${fileType}</div>
                        <div class="document-actions">
                            <button class="action-btn open-btn" onclick="openFileInBrowser('http://localhost:3000${file.url}')">Open</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }
        
        return html;
    }
    
    renderImageGrid(imageFiles, messageId) {
        const count = imageFiles.length;
        let gridClass = '';
        
        // Determine grid layout based on number of images
        if (count === 1) {
            gridClass = 'single-image';
        } else if (count === 2) {
            gridClass = 'two-images';
        } else if (count === 3) {
            gridClass = 'three-images';
        } else if (count === 4) {
            gridClass = 'four-images';
        } else if (count === 5) {
            gridClass = 'five-images';
            } else {
            gridClass = 'six-plus-images';
        }
        
        let html = `<div class="image-grid ${gridClass}">`;
        
        imageFiles.forEach((file, index) => {
            const showOverlay = count > 5 && index === 4; // Show "+X" on 5th image if more than 5
            const imageId = `image-${messageId}-${index}`;
            
            html += `<div class="grid-image-item" data-image-id="${imageId}" data-image-url="http://localhost:3000${file.url}" data-image-name="${file.originalName}">
                <img src="http://localhost:3000${file.url}" alt="${file.originalName}" 
                     class="grid-image cursor-pointer" 
                     onclick="window.open('http://localhost:3000${file.url}', '_blank')"
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                ${showOverlay ? `<div class="image-overlay">+${count - 5}</div>` : ''}
            </div>`;
        });
        
        html += '</div>';
        return html;
    }
    
    renderFileAttachment(file) {
        // Legacy function for single file - now uses multiple file function
        return this.renderFileAttachments([file]);
    }
    
    getFileType(filename, mimetype) {
        const extension = filename.split('.').pop().toLowerCase();
        
        if (mimetype) {
            if (mimetype.includes('pdf')) return 'PDF Document';
            if (mimetype.includes('word') || mimetype.includes('document')) return 'Microsoft Word Document';
            if (mimetype.includes('excel') || mimetype.includes('spreadsheet')) return 'Microsoft Excel Document';
            if (mimetype.includes('powerpoint') || mimetype.includes('presentation')) return 'Microsoft PowerPoint Document';
            if (mimetype.includes('text')) return 'Text Document';
            if (mimetype.includes('zip') || mimetype.includes('rar')) return 'Archive File';
        }
        
        switch (extension) {
            case 'pdf': return 'PDF Document';
            case 'doc':
            case 'docx': return 'Microsoft Word Document';
            case 'xls':
            case 'xlsx': return 'Microsoft Excel Document';
            case 'ppt':
            case 'pptx': return 'Microsoft PowerPoint Document';
            case 'txt': return 'Text Document';
            case 'zip':
            case 'rar':
            case '7z': return 'Archive File';
            default: return 'Document';
        }
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
    
    getFileIcon(fileType) {
        if (fileType.includes('PDF')) {
            return `<div class="pdf-icon">
                <div class="pdf-icon-body">
                    <div class="pdf-icon-corner"></div>
                    <div class="pdf-text">PDF</div>
                </div>
            </div>`;
        } else if (fileType.includes('Word')) {
            return `<div class="word-icon">
                <div class="word-icon-body">
                    <div class="word-icon-corner"></div>
                    <div class="word-text">DOC</div>
                </div>
            </div>`;
        } else if (fileType.includes('Excel')) {
            return `<div class="excel-icon">
                <div class="excel-icon-body">
                    <div class="excel-icon-corner"></div>
                    <div class="excel-text">XLS</div>
                </div>
            </div>`;
        } else {
            return `<div class="generic-icon">
                <div class="generic-icon-body">
                    <div class="generic-icon-corner"></div>
                    <div class="generic-text">DOC</div>
                </div>
            </div>`;
        }
    }
    
    getReplyData(messageId) {
        console.log('getReplyData called for messageId:', messageId);
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        console.log('Found message element:', messageElement);
        
        if (messageElement) {
            console.log('Message element HTML:', messageElement.innerHTML);
            
            const senderElement = messageElement.querySelector('.message-sender');
            const textElement = messageElement.querySelector('.message-text');
            // Check for both old structure (.file-attachment img) and new structure (.grid-image)
            const imageElement = messageElement.querySelector('.file-attachment img') || messageElement.querySelector('.grid-image');
            
            console.log('Elements found:', {
                sender: senderElement,
                text: textElement,
                image: imageElement
            });
            
            // Also check for any img elements in the message
            const allImages = messageElement.querySelectorAll('img');
            console.log('All images in message:', allImages);
            
            // Check if this is an image message
            if (imageElement) {
                const replyData = {
                    sender: senderElement ? senderElement.textContent : 'Unknown',
                    text: 'Image',
                    imageUrl: imageElement.src,
                    isImageReply: true
                };
                
                console.log('Getting reply data for image message ID:', messageId, 'Data:', replyData);
                
                // Store reply data in localStorage for persistence
                this.storeReplyData(messageId, replyData);
                
                return replyData;
            } else if (senderElement && textElement) {
                const replyData = {
                    sender: senderElement.textContent,
                    text: textElement.textContent
                };
                
                console.log('Getting reply data for text message ID:', messageId, 'Data:', replyData);
                
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
    
    clearAllReplyData() {
        localStorage.removeItem('chat_replies');
        console.log('Cleared all reply data from localStorage');
    }
    
    clearIncorrectReplyData() {
        // Clean up any reply data stored under wrong message IDs
        const storedReplies = JSON.parse(localStorage.getItem('chat_replies') || '{}');
        const currentMessageIds = Array.from(document.querySelectorAll('[data-message-id]')).map(el => el.getAttribute('data-message-id'));
        
        let cleaned = false;
        Object.keys(storedReplies).forEach(messageId => {
            // If this message ID doesn't exist in current messages, it might be incorrect
            if (!currentMessageIds.includes(messageId)) {
                console.log('Removing potentially incorrect reply data for message ID:', messageId);
                delete storedReplies[messageId];
                cleaned = true;
            }
        });
        
        if (cleaned) {
            localStorage.setItem('chat_replies', JSON.stringify(storedReplies));
            console.log('Cleaned up incorrect reply data');
        }
    }

    restoreReplyData() {
        // This function will be called after messages are loaded to restore reply data
        console.log('Restoring reply data for existing messages...');
        
        // First clean up any incorrect data
        this.clearIncorrectReplyData();
        
        // Check multiple times to ensure we catch all messages
        const checkAndRestore = () => {
            const messages = document.querySelectorAll('[data-message-id]');
            console.log('Found', messages.length, 'messages to check for reply data');
            
            messages.forEach(messageElement => {
                const messageId = messageElement.getAttribute('data-message-id');
                const replyPreview = messageElement.querySelector('.reply-preview');
                
                // Only restore reply previews to messages that actually contain replies
                // Check if this message has reply data in its data attributes or content
                const hasReplyData = messageElement.getAttribute('data-reply-to') || 
                                   messageElement.querySelector('[data-reply-to]') ||
                                   messageElement.getAttribute('data-reply-to-id');
                
                // If message has no reply preview but should have one (has reply data), try to restore it
                if (!replyPreview && messageId && hasReplyData) {
                    const storedReplyData = this.getStoredReplyData(messageId);
                    if (storedReplyData) {
                        console.log('Restoring reply preview for message with reply data:', messageId);
                        // Check if this message already has a username (from initial rendering)
                        const hasExistingUsername = messageElement.querySelector('.message-sender') || 
                            (messageElement.querySelector('.message-content').firstChild && 
                             messageElement.querySelector('.message-content').firstChild.style && 
                             messageElement.querySelector('.message-content').firstChild.style.fontWeight === '600');
                        
                        // Check if this is an image message
                        const isImageMessage = messageElement.querySelector('.file-attachment img') || 
                            messageElement.querySelector('[data-message-id]') && 
                            messageElement.querySelector('.image-caption');
                        
                        // Check if this is the current user's message
                        const isOwnMessage = messageElement.classList.contains('own');
                        
                        if (hasExistingUsername) {
                            console.log('Message already has username, only adding reply preview');
                            // Only add the reply preview, not the username
                            this.addReplyPreviewOnly(messageElement, storedReplyData, isOwnMessage);
                        } else if (isImageMessage) {
                            console.log('Image message - username is above image, only adding reply preview');
                            // For image messages, username is above the image, so only add reply preview
                            this.addReplyPreviewOnly(messageElement, storedReplyData, isOwnMessage);
                        } else {
                            this.addReplyPreview(messageElement, storedReplyData);
                        }
                    }
                }
            });
        };
        
        // Check immediately and then again after delays
        checkAndRestore();
        setTimeout(checkAndRestore, 1000);
        setTimeout(checkAndRestore, 3000);
        setTimeout(checkAndRestore, 5000);
        
        // Also restore reactions for all existing messages
        setTimeout(() => {
            const messages = document.querySelectorAll('[data-message-id]');
            messages.forEach(messageElement => {
                const messageId = messageElement.getAttribute('data-message-id');
                if (messageId) {
                    restoreReactions(messageId);
                }
            });
        }, 2000);
    }
    
    addReplyPreviewOnly(messageElement, replyData, isOwn = false) {
        const messageContent = messageElement.querySelector('.message-content');
        if (messageContent) {
            console.log('addReplyPreviewOnly called for message:', messageElement.getAttribute('data-message-id'));
            
            // Check if there's already a reply preview
            const existingReplyPreview = messageContent.querySelector('.reply-preview');
            if (existingReplyPreview) {
                console.log('Reply preview already exists, skipping');
                return;
            }
            
            // Add username above reply preview if not own message
            if (!isOwn) {
                const senderNameElement = document.createElement('div');
                senderNameElement.style.cssText = `
                    color: #000000; 
                    font-weight: 600; 
                    margin-bottom: 4px; 
                    font-size: 0.9rem;
                `;
                senderNameElement.textContent = messageElement.getAttribute('data-sender-name') || 'Unknown';
                messageContent.insertBefore(senderNameElement, messageContent.firstChild);
            }
            
            // Create only the reply preview element (no username)
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
                <div style="color: #000000; font-weight: 600; margin-bottom: 2px;">${replyData.sender}</div>
                ${replyData.imageUrl ? `
                    <img src="${replyData.imageUrl}" alt="Reply image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                ` : `
                    <div style="color: #000000; font-style: italic;">${replyData.text.substring(0, 50)}${replyData.text.length > 50 ? '...' : ''}</div>
                `}
            `;
            
            // Insert reply preview at the beginning of message content
            messageContent.insertBefore(replyPreview, messageContent.firstChild);
        }
    }

    addReplyPreview(messageElement, replyData) {
        const messageContent = messageElement.querySelector('.message-content');
        if (messageContent) {
            console.log('addReplyPreview called for message:', messageElement.getAttribute('data-message-id'));
            
            // Check if there's already a reply preview
            const existingReplyPreview = messageContent.querySelector('.reply-preview');
            if (existingReplyPreview) {
                console.log('Reply preview already exists, skipping');
                return;
            }
            
            // Check if there's already a username above where we would add the reply preview
            const firstChild = messageContent.firstChild;
            const hasUsernameAbove = firstChild && 
                firstChild.style && 
                firstChild.style.fontWeight === '600' && 
                firstChild.style.marginBottom === '4px';
            
            console.log('Has username above:', hasUsernameAbove);
            
            // Only add username if there isn't one already
            if (!hasUsernameAbove) {
                // Get the sender name from the message element
                let senderElement = messageElement.querySelector('.message-sender');
                let senderName = 'Unknown';
                
                if (senderElement) {
                    senderName = senderElement.textContent;
                } else {
                    // For image messages, get sender name from data attribute
                    senderName = messageElement.getAttribute('data-sender-name') || 'Unknown';
                }
                
                // Create the sender name element
                const senderNameElement = document.createElement('div');
                senderNameElement.style.cssText = `
                    color: #000000; 
                    font-weight: 600; 
                    margin-bottom: 4px; 
                    font-size: 0.9rem;
                `;
                senderNameElement.textContent = senderName;
                
                // Insert username at the beginning of message content
                messageContent.insertBefore(senderNameElement, messageContent.firstChild);
            }
            
            // Create the reply preview element
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
                <div style="color: #000000; font-weight: 600; margin-bottom: 2px;">${replyData.sender}</div>
                ${replyData.imageUrl ? `
                    <img src="${replyData.imageUrl}" alt="Reply image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                ` : `
                    <div style="color: #000000; font-style: italic;">${replyData.text.substring(0, 50)}${replyData.text.length > 50 ? '...' : ''}</div>
                `}
            `;
            
            // Insert reply preview at the beginning of message content
            messageContent.insertBefore(replyPreview, messageContent.firstChild);
        }
    }
}

// Global functions

let currentContextMessage = null;
let replyToMessageId = null;

// New Sidebar Functions
function toggleNewSidebar() {
    const sidebar = document.getElementById('newSidebar');
    const toggleBtn = document.getElementById('newSidebarToggleBtn');
    const chatContainer = document.querySelector('.chat-container');
    
    if (sidebar && toggleBtn && chatContainer) {
        sidebar.classList.toggle('open');
        chatContainer.classList.toggle('sidebar-open');
        
        // Update toggle button icon with smooth transition
        const icon = toggleBtn.querySelector('svg');
        if (icon) {
            // Add rotation effect during transition
            icon.style.transform = 'rotate(90deg)';
            
            setTimeout(() => {
                if (sidebar.classList.contains('open')) {
                    // Show close icon when sidebar is open
                    icon.innerHTML = '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>';
                } else {
                    // Show menu icon when sidebar is closed
                    icon.innerHTML = '<line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line>';
                }
                icon.style.transform = 'rotate(0deg)';
            }, 150); // Half of the transition duration
        }
    }
}

function showAllMessages() {
    // Show all messages
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => msg.style.display = 'flex');
    
    // Update active button
    document.querySelectorAll('.new-action-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.new-action-btn').classList.add('active');
}


function showMediaMessages() {
    // Show only messages with media
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => {
        const hasMedia = msg.querySelector('.grid-image, .file-attachment, .document-attachment');
        msg.style.display = hasMedia ? 'flex' : 'none';
    });
    
    // Update active button
    document.querySelectorAll('.new-action-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.new-action-btn').classList.add('active');
}

function showChatSettings() {
    // Placeholder for chat settings
    alert('Chat settings will be implemented here!');
}

// Search functionality for new sidebar
function initializeNewSearch() {
    const searchInput = document.getElementById('newSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const messages = document.querySelectorAll('.message');
            
            if (searchTerm === '') {
                // Show all messages
                messages.forEach(msg => msg.style.display = 'flex');
            } else {
                // Filter messages
                messages.forEach(msg => {
                    const messageText = msg.textContent.toLowerCase();
                    msg.style.display = messageText.includes(searchTerm) ? 'flex' : 'none';
                });
            }
        });
    }
}

// Enhanced user list with status for new sidebar
function updateNewUserList(users) {
    console.log('updateNewUserList called with:', users);
    const onlineUsersContainer = document.getElementById('newOnlineUsers');
    const onlineCount = document.getElementById('newOnlineCount');
    
    console.log('onlineUsersContainer:', onlineUsersContainer);
    console.log('onlineCount:', onlineCount);
    
    if (!onlineUsersContainer) {
        console.log('onlineUsersContainer not found!');
        return;
    }
    
    // Update count
    if (onlineCount) {
        onlineCount.textContent = users.length;
    }
    
    // Clear existing users
    onlineUsersContainer.innerHTML = '';
    
    if (users.length === 0) {
        onlineUsersContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">No users online</div>';
        return;
    }
    
    // Add users
    users.forEach(user => {
        const userItem = document.createElement('div');
        userItem.className = 'new-user-item';
        userItem.innerHTML = `
            <div class="new-user-avatar" style="background-color: ${user.color}">
                ${user.name.charAt(0).toUpperCase()}
                <div class="new-user-status online"></div>
            </div>
            <div class="new-user-info">
                <div class="new-user-name">${user.name}</div>
                <div class="new-user-last-seen">Online now</div>
            </div>
        `;
        onlineUsersContainer.appendChild(userItem);
    });
    
    console.log('Added', users.length, 'users to sidebar');
}

// Debug functions
function clearReplyData() {
    if (window.chatInstance) {
        window.chatInstance.clearAllReplyData();
    }
}

function showReplyData() {
    const storedReplies = JSON.parse(localStorage.getItem('chat_replies') || '{}');
    console.log('All stored reply data:', storedReplies);
    return storedReplies;
}

function checkReplyState() {
    console.log('=== REPLY STATE DEBUG ===');
    console.log('replyToMessageId:', replyToMessageId);
    console.log('currentContextMessage:', currentContextMessage);
    console.log('Reply indicator exists:', !!document.querySelector('.reply-indicator'));
    console.log('========================');
}

function clearReplyState() {
    replyToMessageId = null;
    currentContextMessage = null;
    const replyIndicator = document.querySelector('.reply-indicator');
    if (replyIndicator) {
        replyIndicator.remove();
    }
    console.log('Cleared reply state - replyToMessageId:', replyToMessageId);
}

function showContextMenu(event, messageId, messageData) {
    console.log('showContextMenu called with:', { messageId, messageData });
    event.preventDefault();
    event.stopPropagation();
    
    const contextMenu = document.getElementById('messageContextMenu');
    console.log('Context menu element:', contextMenu);
    
    // Don't show context menu for own messages
    if (messageData.senderId === window.chatInstance.currentUser.id) {
        console.log('Not showing context menu for own message');
        return;
    }
    
    currentContextMessage = { id: messageId, data: messageData };
    console.log('Set currentContextMessage to:', currentContextMessage);
    
    // Position the context menu near the clicked element
    const rect = event.target.getBoundingClientRect();
    const contextMenuWidth = 150; // Approximate width of context menu
    const contextMenuHeight = 200; // Approximate height of context menu
    
    // Calculate position relative to the clicked element
    let left = rect.right + 20; // Position 10px to the right of the message
    let top = rect.top + (rect.height / 2) - (contextMenuHeight / 2); // Center vertically on the element
    
    // Adjust if menu would go off right edge
    if (left + contextMenuWidth > window.innerWidth - 10) {
        left = rect.left - contextMenuWidth - 10; // Position to the left of the element instead
    }
    
    // Adjust if menu would go off top edge
    if (top < 10) {
        top = 10; // Stick to top edge
    }
    
    // Adjust if menu would go off bottom edge or overlap with chat input
    const chatInputArea = document.querySelector('.chat-input-container');
    const chatInputTop = chatInputArea ? chatInputArea.getBoundingClientRect().top : window.innerHeight - 100;
    
    if (top + contextMenuHeight > chatInputTop - 10) {
        // Position above the chat input area
        top = chatInputTop - contextMenuHeight - 20;
    }
    
    // Final check for window bottom edge
    if (top + contextMenuHeight > window.innerHeight - 10) {
        top = window.innerHeight - contextMenuHeight - 10;
    }
    
    // Ensure menu doesn't go off left or top edges
    left = Math.max(10, left);
    top = Math.max(10, top);
    
    contextMenu.style.left = left + 'px';
    contextMenu.style.top = top + 'px';
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
    const currentUserId = window.chatInstance.currentUser.id;
    
    if (messageElement) {
        let reactionsContainer = messageElement.querySelector('.emoji-reactions');
        if (!reactionsContainer) {
            reactionsContainer = document.createElement('div');
            reactionsContainer.className = 'emoji-reactions';
            messageElement.querySelector('.message-content').appendChild(reactionsContainer);
        }
        
        // Remove user's previous reaction if any
        const userPreviousReaction = reactionsContainer.querySelector(`[data-user-id="${currentUserId}"]`);
        if (userPreviousReaction) {
            const previousEmoji = userPreviousReaction.dataset.emoji;
            const previousCount = parseInt(userPreviousReaction.dataset.count);
            
            if (previousCount > 1) {
                // Decrease count of previous emoji
                userPreviousReaction.dataset.count = (previousCount - 1).toString();
                userPreviousReaction.innerHTML = `${previousEmoji} ${previousCount - 1}`;
            } else {
                // Remove previous emoji completely
                userPreviousReaction.remove();
            }
        }
        
        // Check if the new emoji already exists
        const existingReaction = reactionsContainer.querySelector(`[data-emoji="${emoji}"]`);
        if (existingReaction) {
            // Increment count and add user ID
            const count = parseInt(existingReaction.dataset.count) + 1;
            existingReaction.dataset.count = count;
            existingReaction.innerHTML = `${emoji} ${count}`;
            existingReaction.dataset.userId = currentUserId;
            existingReaction.style.cursor = 'pointer';
            // Remove existing click listener if any
            existingReaction.replaceWith(existingReaction.cloneNode(true));
            // Add new click listener
            const newReaction = reactionsContainer.querySelector(`[data-emoji="${emoji}"]`);
            newReaction.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                newReaction.blur();
                const messageData = {
                    id: messageId,
                    sender: messageElement.dataset.senderName || 'Unknown'
                };
                showContextMenu(e, messageId, messageData);
            });
        } else {
            // Add new reaction
            const reactionElement = document.createElement('div');
            reactionElement.className = 'emoji-reaction';
            reactionElement.dataset.emoji = emoji;
            reactionElement.dataset.count = '1';
            reactionElement.dataset.userId = currentUserId;
            reactionElement.innerHTML = `${emoji} 1`;
            reactionElement.style.cursor = 'pointer';
            reactionElement.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                reactionElement.blur();
                const messageData = {
                    id: messageId,
                    sender: messageElement.dataset.senderName || 'Unknown'
                };
                showContextMenu(e, messageId, messageData);
            });
            reactionsContainer.appendChild(reactionElement);
        }
        
        // Store reactions in localStorage for persistence
        storeReactions(messageId, reactionsContainer);
        
        // Send reaction to server
        if (window.chatInstance && window.chatInstance.socket) {
            window.chatInstance.socket.emit('message-reaction', {
                messageId: messageId,
                emoji: emoji,
                userId: currentUserId
            });
        }
    }
    
    hideContextMenu();
}

function storeReactions(messageId, reactionsContainer) {
    const reactions = {};
    const reactionElements = reactionsContainer.querySelectorAll('.emoji-reaction');
    
    reactionElements.forEach(element => {
        const emoji = element.dataset.emoji;
        const count = parseInt(element.dataset.count);
        const userId = element.dataset.userId;
        reactions[emoji] = { count: count, userId: userId };
    });
    
    // Store in localStorage
    const storedReactions = JSON.parse(localStorage.getItem('chat_reactions') || '{}');
    storedReactions[messageId] = reactions;
    localStorage.setItem('chat_reactions', JSON.stringify(storedReactions));
    
    console.log('Stored reactions for message:', messageId, reactions);
}

function restoreReactions(messageId) {
    const storedReactions = JSON.parse(localStorage.getItem('chat_reactions') || '{}');
    const messageReactions = storedReactions[messageId];
    
    if (messageReactions) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            let reactionsContainer = messageElement.querySelector('.emoji-reactions');
            if (!reactionsContainer) {
                reactionsContainer = document.createElement('div');
                reactionsContainer.className = 'emoji-reactions';
                messageElement.querySelector('.message-content').appendChild(reactionsContainer);
            }
            
            // Clear existing reactions
            reactionsContainer.innerHTML = '';
            
            // Restore reactions
            Object.entries(messageReactions).forEach(([emoji, reactionData]) => {
                const reactionElement = document.createElement('div');
                reactionElement.className = 'emoji-reaction';
                reactionElement.dataset.emoji = emoji;
                reactionElement.dataset.count = reactionData.count.toString();
                reactionElement.dataset.userId = reactionData.userId;
                reactionElement.innerHTML = `${emoji} ${reactionData.count}`;
                reactionElement.style.cursor = 'pointer';
                reactionElement.addEventListener('click', (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    reactionElement.blur();
                    const messageData = {
                        id: messageId,
                        sender: messageElement.dataset.senderName || 'Unknown'
                    };
                    showContextMenu(e, messageId, messageData);
                });
                reactionsContainer.appendChild(reactionElement);
            });
            
            console.log('Restored reactions for message:', messageId, messageReactions);
        }
    }
}

function replyToMessage() {
    console.log('replyToMessage called');
    console.log('currentContextMessage:', currentContextMessage);
    
    if (!currentContextMessage) {
        console.log('No currentContextMessage, returning');
        return;
    }
    
    replyToMessageId = currentContextMessage.id;
    const messageData = currentContextMessage.data;
    
    console.log('Setting replyToMessageId to:', replyToMessageId);
    console.log('messageData:', messageData);
    
    // Get the complete reply data including image information
    const replyData = window.chatInstance.getReplyData(replyToMessageId);
    console.log('Got reply data:', replyData);
    
    if (window.chatInstance && replyData) {
        // Store under the original message ID (the one being replied to)
        window.chatInstance.storeReplyData(replyToMessageId, replyData);
        console.log('Stored reply data for original message ID:', replyToMessageId, 'with data:', replyData);
    }
    
    // Show reply indicator in input area
    showReplyIndicator(replyData);
    
    // Focus on message input
    const messageInput = document.getElementById('messageInput');
    messageInput.focus();
    
    hideContextMenu();
}

function showReplyIndicator(messageData) {
    console.log('showReplyIndicator called with:', messageData);
    
    // Check if messageData is null or undefined
    if (!messageData) {
        console.log('No messageData provided to showReplyIndicator');
        return;
    }
    
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
    
    // Create content based on whether it's an image reply or text reply
    let contentHtml = '';
    if (messageData.isImageReply && messageData.imageUrl) {
        contentHtml = `
            <div style="color: #2563eb; font-weight: 600; margin-bottom: 4px; font-size: 0.9rem;">${messageData.sender}</div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <img src="${messageData.imageUrl}" alt="Reply image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                <div style="color: #333; font-size: 0.85rem;">Image</div>
            </div>
            <div style="color: #666; font-size: 0.8rem; margin-bottom: 8px;">Replying to this image</div>
        `;
    } else {
        contentHtml = `
            <div style="color: #2563eb; font-weight: 600; margin-bottom: 4px; font-size: 0.9rem;">${messageData.sender}</div>
            <div style="color: #333; font-size: 0.85rem; margin-bottom: 8px;">${messageData.text ? messageData.text.substring(0, 50) : 'No text'}${messageData.text && messageData.text.length > 50 ? '...' : ''}</div>
            <div style="color: #666; font-size: 0.8rem; margin-bottom: 8px;">Replying to this message</div>
        `;
    }
    
    replyIndicator.innerHTML = `
        ${contentHtml}
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

function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}



function openFileInBrowser(url) {
    // Create a link without the download attribute to open in browser
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    
    // For PDFs and documents, try to open in browser
    if (url.toLowerCase().includes('.pdf') || 
        url.toLowerCase().includes('.doc') || 
        url.toLowerCase().includes('.docx') ||
        url.toLowerCase().includes('.txt')) {
        // Open directly in new tab
        window.open(url, '_blank');
    } else {
        // For other files, use the link method
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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


