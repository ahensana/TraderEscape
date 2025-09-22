<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/community_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // User is not logged in, show login popup instead of chat
    $showLoginPopup = true;
    $currentUser = null;
    $hasCommunityAccess = false;
} else {
    // User is logged in, get their information
    $currentUser = getCurrentUser();
    $showLoginPopup = false;
    
    // Check if user has community access
    $hasCommunityAccess = hasCommunityAccess($currentUser['id']);
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
    
    /* Sidebar content area - scrollable */
    .new-sidebar-content {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
    }
    
    /* Custom scrollbar for sidebar */
    .new-sidebar-content::-webkit-scrollbar {
        width: 6px;
    }
    
    .new-sidebar-content::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }
    
    .new-sidebar-content::-webkit-scrollbar-thumb {
        background: rgba(37, 99, 235, 0.5);
        border-radius: 3px;
    }
    
    .new-sidebar-content::-webkit-scrollbar-thumb:hover {
        background: rgba(37, 99, 235, 0.7);
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
        padding: 23px 20px;
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
        flex: 0 0 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    
    .new-section-header {
        padding: 16px 20px 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(37, 99, 235, 0.1);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .new-section-header:hover {
        background: rgba(37, 99, 235, 0.1);
    }
    
    .new-section-header.collapsible {
        cursor: pointer;
    }
    
    .new-section-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }
    
    .new-section-arrow {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
        color: rgba(255, 255, 255, 0.6);
        transform: rotate(-90deg);
    }
    
    .new-section-arrow.expanded {
        transform: rotate(0deg);
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
        max-height: 200px;
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
        display: none;
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
    
    .new-user-name-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }
    
    .new-user-name {
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }
    
    .admin-status {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        font-size: 0.8rem;
        font-weight: 500;
        margin-left: 8px;
        padding: 2px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }
    
    .new-user-last-seen {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
        margin: 0;
    }
    
    .admin-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Community Requests Styles */
    .new-requests-count {
        background: rgba(245, 158, 11, 0.3);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .new-requests-list {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        max-height: 0;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .new-requests-list.expanded {
        max-height: 300px;
        padding: 8px 0;
    }

    .new-request-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(37, 99, 235, 0.1);
        transition: all 0.2s ease;
    }

    .new-request-item:hover {
        background: rgba(37, 99, 235, 0.1);
    }

    .new-request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .new-request-user {
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .new-request-status {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .new-request-status.pending {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .new-request-status.approved {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .new-request-status.rejected {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    .new-request-message {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        margin: 4px 0;
    }

    .new-request-actions {
        display: flex;
        gap: 6px;
        margin-top: 4px;
    }

    .new-request-btn {
        padding: 4px 8px;
        border: none;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
    }

    .new-request-btn.approve {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .new-request-btn.approve:hover {
        background: rgba(16, 185, 129, 0.3);
    }

    .new-request-btn.reject {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .new-request-btn.reject:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    .new-request-btn.remove {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .new-request-btn.remove:hover {
        background: rgba(245, 158, 11, 0.3);
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
    
    .leave-chat-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
    
    /* Leave Chat Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    
    .modal-content {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: 16px;
        padding: 0;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .modal-header h3 {
        color: white;
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .modal-body {
        padding: 24px;
        text-align: center;
    }
    
    .modal-icon {
        margin-bottom: 16px;
        display: flex;
        justify-content: center;
    }
    
    .modal-body p {
        color: white;
        margin: 0 0 8px 0;
        font-size: 1rem;
        font-weight: 500;
    }
    
    .modal-subtitle {
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 0.9rem !important;
        font-weight: 400 !important;
        margin-bottom: 0 !important;
    }
    
    .modal-footer {
        display: flex;
        gap: 12px;
        padding: 16px 24px 24px;
        justify-content: center;
    }
    
    /* Confirmation Modal Styles */
    .confirmation-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        backdrop-filter: blur(5px);
    }
    
    .confirmation-modal-content {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: 16px;
        padding: 0;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: modalSlideIn 0.3s ease-out;
    }
    
    .confirmation-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .confirmation-modal-header h3 {
        color: white;
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .confirmation-modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        font-size: 24px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .confirmation-modal-close:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .confirmation-modal-body {
        padding: 24px;
        text-align: center;
    }
    
    .confirmation-modal-body p {
        color: white;
        margin: 0;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.5;
    }
    
    .confirmation-modal-footer {
        display: flex;
        gap: 12px;
        padding: 16px 24px 24px;
        justify-content: center;
    }
    
    .confirmation-btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 80px;
    }
    
    .confirmation-btn.cancel {
        background: rgba(107, 114, 128, 0.2);
        color: #9ca3af;
        border: 1px solid rgba(107, 114, 128, 0.3);
    }
    
    .confirmation-btn.cancel:hover {
        background: rgba(107, 114, 128, 0.3);
        color: #d1d5db;
    }
    
    .confirmation-btn.confirm {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    
    .confirmation-btn.confirm:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .confirmation-btn.confirm.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .confirmation-btn.confirm.danger:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* User Management Modal Styles */
    .user-management-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10002;
        backdrop-filter: blur(5px);
    }

    .user-management-modal-content {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: 16px;
        padding: 0;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: modalSlideIn 0.3s ease-out;
    }

    .user-management-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-management-modal-header h3 {
        color: white;
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .user-management-modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        font-size: 24px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .user-management-modal-close:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .user-management-modal-body {
        padding: 24px;
    }

    .user-info {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-avatar-large {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        color: white;
        margin-right: 16px;
    }

    .user-details h4 {
        color: white;
        margin: 0 0 4px 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .user-details p {
        color: rgba(255, 255, 255, 0.6);
        margin: 0;
        font-size: 0.9rem;
    }

    .user-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .user-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 8px;
        border: none;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }

    .user-action-btn.make-admin {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .user-action-btn.make-admin:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .user-action-btn.remove-user {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .user-action-btn.remove-user:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    
    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        transform: translateY(-1px);
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
    
    .context-menu-delete {
        color: #dc2626 !important;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        margin-top: 4px;
        padding-top: 12px;
    }
    
    .context-menu-delete:hover {
        background: rgba(220, 38, 38, 0.1) !important;
        color: #dc2626 !important;
    }
    
    /* Deleted Message Styles */
.message-deleted {
    opacity: 0.8 !important;
    background: #f3f4f6 !important;
    border-radius: 12px !important;
    border: none !important;
    margin: 8px 0 !important;
}

/* Override background for both own and regular deleted messages */
.message-deleted.own {
    background: #f3f4f6 !important;
}

.message-deleted .message-content {
    background: transparent !important;
    border-radius: 12px !important;
    padding: 0 !important;
}

.deleted-message {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: #6b7280 !important;
    font-style: italic;
    background: transparent !important;
    border-radius: 12px;
}

.deleted-message i {
    margin-right: 8px;
    color: #dc2626 !important;
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
    
    /* Image Modal */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(10px);
    }
    
    .image-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .image-modal-content {
        max-width: 90%;
        max-height: 90%;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .image-modal img {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    }
    
    .image-modal-header {
        color: white;
        text-align: center;
        margin-bottom: 10px;
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .image-modal-close {
        position: absolute;
        top: -40px;
        right: 0;
        color: white;
        font-size: 2rem;
        font-weight: bold;
        cursor: pointer;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .image-modal-close:hover {
        background: rgba(0, 0, 0, 0.8);
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
        display: none;
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
        gap: 50px;
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
<?php elseif (!$hasCommunityAccess): ?>
<!-- Community Access Required Popup -->
<div class="login-popup-overlay" id="communityAccessPopup">
    <div class="login-popup">
        <button class="close-btn" onclick="closeCommunityAccessPopup()">&times;</button>
        <img src="assets/bubble-chat.png" alt="Chat Icon" class="chat-icon" style="width: 80px; height: 80px; object-fit: contain; filter: brightness(0) invert(1); display: block; margin: 0 auto 20px auto;">
        <h2>Access Revoked</h2>
        <p>You have been removed from our trading community due to a violation of the community guidelines. To regain access, you will need to submit a new request for access.</p>
        <div class="login-buttons">
            <button class="login-btn login-btn-primary" onclick="submitCommunityRequest()">Request Access</button>
            <button class="login-btn login-btn-secondary" onclick="closeCommunityAccessPopup()">Maybe Later</button>
        </div>
        <div id="requestMessage" class="message-display" style="display: none; margin-top: 15px; text-align: center; font-size: 14px; color: #10b981;"></div>
    </div>
</div>
<?php endif; ?>

<script>
function closeLoginPopup() {
    // Redirect to home page when popup is closed
    window.location.href = 'index.php';
}

function closeCommunityAccessPopup() {
    // Redirect to home page when popup is closed
    window.location.href = 'index.php';
}

function submitCommunityRequest() {
    const requestBtn = document.querySelector('#communityAccessPopup .login-btn-primary');
    const messageDiv = document.getElementById('requestMessage');
    
    // Show loading state
    requestBtn.disabled = true;
    requestBtn.innerHTML = 'Requesting...';
    
    // Create form data
    const formData = new FormData();
    formData.append('message', ''); // Empty message for simplified flow
    
    // Submit the request
    fetch('./submit_community_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            messageDiv.style.display = 'block';
            messageDiv.textContent = 'Request submitted successfully!';
            messageDiv.style.color = '#10b981';
            
            // Hide message after 2 seconds and redirect
            setTimeout(() => {
                messageDiv.style.display = 'none';
                window.location.href = 'index.php';
            }, 2000);
        } else {
            // Show error message
            messageDiv.style.display = 'block';
            messageDiv.textContent = data.message || 'An error occurred. Please try again.';
            messageDiv.style.color = '#ef4444';
            
            // Reset button
            requestBtn.disabled = false;
            requestBtn.innerHTML = 'Request Access';
            
            // Hide message after 3 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message
        messageDiv.style.display = 'block';
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.style.color = '#ef4444';
        
        // Reset button
        requestBtn.disabled = false;
        requestBtn.innerHTML = 'Request Access';
        
        // Hide message after 3 seconds
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    });
}

function togglePendingMembers() {
    const requestsList = document.getElementById('newRequestsList');
    const arrow = document.querySelector('.new-section-arrow');
    
    if (requestsList && arrow) {
        if (requestsList.classList.contains('expanded')) {
            // Collapse
            requestsList.classList.remove('expanded');
            arrow.classList.remove('expanded');
        } else {
            // Expand
            requestsList.classList.add('expanded');
            arrow.classList.add('expanded');
        }
    }
}
</script>

<!-- New Independent Sidebar -->
<div class="new-sidebar" id="newSidebar">
    <!-- Sidebar Header - Fixed -->
    <div class="new-sidebar-header">
        <div class="new-sidebar-title">
            <img src="assets/bubble-chat.png" alt="Chat Icon" class="new-sidebar-icon">
            <h2>Community Chat</h2>
        </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="new-sidebar-content">
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

        <!-- Community Requests Section (Admin Only) -->
        <?php if (isLoggedIn() && $currentUser && isAdmin($currentUser['id'])): ?>
                <div class="new-sidebar-section" id="adminRequestsSection">
                    <div class="new-section-header collapsible" onclick="togglePendingMembers()">
                        <div class="new-section-toggle">
                            <svg class="new-section-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                            <h3>Pending Members</h3>
                        </div>
                        <span class="new-requests-count" id="newRequestsCount">0</span>
                    </div>
            <div class="new-requests-list" id="newRequestsList">
                <!-- Community requests will be populated here -->
            </div>
        </div>
        <?php endif; ?>

        <!-- Community Members Section -->
    <div class="new-sidebar-section">
        <div class="new-section-header">
                <h3>Community Members</h3>
            <span class="new-online-count" id="newOnlineCount">0</span>
        </div>
        <div class="new-online-users" id="newOnlineUsers">
                <!-- Community members will be populated here -->
            </div>
        </div>
    </div>

    <!-- Chat Settings - Fixed at bottom -->
    <div class="new-sidebar-footer">
        <button class="new-settings-btn" onclick="goToHome()" title="Back to Home">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9,22 9,12 15,12 15,22"></polyline>
            </svg>
            <span>Back to Home</span>
        </button>
        <?php if (!isAdmin($currentUser['id'])): ?>
        <button class="new-settings-btn leave-chat-btn" onclick="leaveChat()" title="Leave Chat">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16,17 21,12 16,7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Leave Chat</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Leave Chat Confirmation Modal -->
<div id="leaveChatModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Leave Chat</h3>
            <button class="modal-close" onclick="closeLeaveChatModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16,17 21,12 16,7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <p>Are you sure you want to leave the chat?</p>
            <p class="modal-subtitle">You will be disconnected from the chat and redirected to the home page.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeLeaveChatModal()">Cancel</button>
            <button class="btn-danger" onclick="confirmLeaveChat()">Leave Chat</button>
        </div>
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
            <div class="context-menu-item context-menu-delete" id="deleteMessageOption" onclick="deleteMessage()" style="display: none;">
                <i class="bi bi-trash"></i>
                <span>Delete this message</span>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="deleteConfirmationModal" class="confirmation-modal" style="display: none;">
            <div class="confirmation-modal-content">
                <div class="confirmation-modal-header">
                    <h3>Delete Message</h3>
                    <button class="confirmation-modal-close" onclick="hideDeleteConfirmationModal()" aria-label="Close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="confirmation-modal-body">
                    <p>Are you sure you want to delete this message? This action cannot be undone.</p>
                </div>
                <div class="confirmation-modal-footer">
                    <button class="confirmation-btn cancel" onclick="hideDeleteConfirmationModal()">Cancel</button>
                    <button class="confirmation-btn confirm danger" onclick="confirmDeleteMessage()">Delete Message</button>
                </div>
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

<!-- User Management Modal -->
<div id="userManagementModal" class="user-management-modal">
    <div class="user-management-modal-content">
        <div class="user-management-modal-header">
            <h3 id="userManagementTitle">Manage User</h3>
            <button class="user-management-modal-close" onclick="hideUserManagementModal()">&times;</button>
        </div>
        <div class="user-management-modal-body">
            <div class="user-info">
                <div class="user-avatar-large" id="userManagementAvatar"></div>
                <div class="user-details">
                    <h4 id="userManagementName"></h4>
                </div>
            </div>
            <div class="user-actions">
                <button class="user-action-btn make-admin" onclick="makeUserAdmin()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Make Admin
                </button>
                <button class="user-action-btn remove-user" onclick="removeUser()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"></path>
                    </svg>
                    Remove User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-modal-content">
        <div class="confirmation-modal-header">
            <h3 id="confirmationTitle">Confirm Action</h3>
            <button class="confirmation-modal-close" onclick="hideConfirmationModal()">&times;</button>
        </div>
        <div class="confirmation-modal-body">
            <p id="confirmationMessage">Are you sure you want to perform this action?</p>
        </div>
        <div class="confirmation-modal-footer">
            <button class="confirmation-btn cancel" onclick="hideConfirmationModal()">Cancel</button>
            <button class="confirmation-btn confirm" id="confirmActionBtn">Confirm</button>
        </div>
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
        
        // Load community requests if user is admin
        if (window.userData && window.userData.is_admin) {
            this.loadCommunityRequests();
        }
        
        // Load community members for all users
        this.loadCommunityMembers();
        
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
                name: window.userData.username, // Use username instead of full_name
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
            console.log('User joined event:', data);
            // No status updates - users remain as "Community member" always
        });
        
        this.socket.on('user-left', (data) => {
            console.log('User left event:', data);
            // No status updates - users remain as "Community member" always
        });
        
        this.socket.on('user-kicked', (data) => {
            console.log('User kicked event:', data);
            // Redirect to home page
            window.location.href = 'index.php';
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

        this.socket.on('message_deleted', (data) => {
            console.log('Message deleted by admin:', data.messageId);
            console.log('Deleted by:', data.deletedBy);
            console.log('Deleted message:', data.deletedMessage);
            
            const messageElement = document.querySelector(`[data-message-id="${data.messageId}"]`);
            if (messageElement) {
                console.log('Found message element, updating to show deletion');
                
                // Update the message content to show deletion indicator
                const messageContent = messageElement.querySelector('.message-content');
                if (messageContent) {
                    // Check if this is the current user's own message using the original sender ID
                    const isOwnMessage = data.originalSenderId && data.originalSenderId.startsWith(this.currentUser.id);
                    const deletionText = isOwnMessage 
                        ? `Your message was deleted by ${data.deletedBy}`
                        : `This message was deleted by ${data.deletedBy}`;
                    
                    messageContent.innerHTML = `
                        <div class="deleted-message">
                            <i class="bi bi-trash" style="color: #dc2626; margin-right: 8px;"></i>
                            <span style="color: #6b7280; font-style: italic;">${deletionText}</span>
                        </div>
                    `;
                }
                
                // Add deleted class for styling and ensure consistent appearance
                messageElement.classList.add('message-deleted');
                // Keep 'own' class for positioning but override background color
                messageElement.style.background = '#f3f4f6 !important';
                messageElement.style.borderRadius = '12px';
                messageElement.style.border = 'none';
                messageElement.style.margin = '8px 0';
                
                // Remove any file attachments
                const fileAttachments = messageElement.querySelector('.file-attachment');
                if (fileAttachments) {
                    fileAttachments.remove();
                }
                
                // Remove any reactions
                const reactionsContainer = messageElement.querySelector('.emoji-reactions');
                if (reactionsContainer) {
                    reactionsContainer.remove();
                }
                
                // Remove reply preview if exists
                const replyPreview = messageElement.querySelector('.reply-preview');
                if (replyPreview) {
                    replyPreview.remove();
                }
                
            } else {
                console.log('Message element not found in DOM for ID:', data.messageId);
                // Try to find by different selectors as fallback
                const allMessages = document.querySelectorAll('[data-message-id]');
                console.log('Available message IDs in DOM:', Array.from(allMessages).map(el => el.getAttribute('data-message-id')));
            }
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
        
        // Check if message is deleted
        const isDeleted = messageData.isDeleted || false;
        if (isDeleted) {
            messageElement.classList.add('message-deleted');
            // Keep 'own' class for positioning but override background color
            // Ensure consistent grey styling for deleted messages
            messageElement.style.background = '#f3f4f6 !important';
            messageElement.style.borderRadius = '12px';
            messageElement.style.border = 'none';
            messageElement.style.margin = '8px 0';
            
            // Debug logging
            console.log('Deleted message debug:', {
                originalSenderId: messageData.originalSenderId,
                currentUserId: this.currentUser.id,
                isOwnMessage: messageData.originalSenderId && messageData.originalSenderId.startsWith(this.currentUser.id),
                deletedBy: messageData.deletedBy
            });
        }
        
        messageElement.innerHTML = `
            ${!isOwn ? `<div class="message-avatar" style="background-color: ${messageData.color}">
                ${messageData.sender.charAt(0).toUpperCase()}
            </div>` : ''}
            <div class="message-content">
                ${isDeleted ? `
                    <div class="deleted-message">
                        <i class="bi bi-trash" style="color: #dc2626; margin-right: 8px;"></i>
                        <span style="color: #6b7280; font-style: italic;">${messageData.originalSenderId && messageData.originalSenderId.startsWith(this.currentUser.id) ? 'Your message was deleted by' : 'This message was deleted by'} ${messageData.deletedBy || 'admin'}</span>
                    </div>
                ` : `
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
                `}
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
        // Don't update community members from socket users - only show approved users list
        // updateNewUserList(users);
        
        // No community members updates from socket - only show approved users list
        // this.updateCommunityMembersOnlineStatus(users);
        
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
    
    // Update online status for all community members
    updateCommunityMembersOnlineStatus(onlineUsers) {
        // Get all community member elements
        const memberElements = document.querySelectorAll('[data-user-id]');
        
        memberElements.forEach(memberElement => {
            const userId = memberElement.getAttribute('data-user-id');
            // Check if any online user has a baseId that matches this user ID
            const isOnline = onlineUsers.some(user => {
                // Extract numeric ID from baseId (e.g., "user_3" -> "3")
                const numericBaseId = user.baseId ? user.baseId.replace('user_', '') : null;
                return numericBaseId == userId;
            });
            
            this.updateUserOnlineStatus(userId, isOnline);
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
                     onclick="openImageModal('http://localhost:3000${file.url}', '${file.originalName}')"
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

    async loadCommunityRequests() {
        try {
            const response = await fetch('get_community_requests.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateCommunityRequestsUI(data.requests);
            } else {
                console.error('Failed to load community requests:', data.message);
            }
        } catch (error) {
            console.error('Error loading community requests:', error);
        }
    }
    
    async loadCommunityMembers() {
        try {
            console.log('Loading community members...');
            const response = await fetch('get_community_members.php');
            const data = await response.json();
            
            console.log('Community members response:', data);
            
            if (data.success) {
                console.log('Loaded community members:', data.members);
                this.updateCommunityMembersUI(data.members);
            } else {
                console.error('Failed to load community members:', data.message);
                console.error('Debug info:', data.debug);
            }
        } catch (error) {
            console.error('Error loading community members:', error);
        }
    }

    updateCommunityRequestsUI(requests) {
        const requestsList = document.getElementById('newRequestsList');
        const requestsCount = document.getElementById('newRequestsCount');
        
        if (!requestsList || !requestsCount) return;
        
        // Update count
        requestsCount.textContent = requests.length;
        
        // Clear existing requests
        requestsList.innerHTML = '';
        
        if (requests.length === 0) {
            requestsList.innerHTML = '<div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.8rem;">No requests</div>';
            return;
        }
        
        // Add each request
        requests.forEach(request => {
            const requestItem = document.createElement('div');
            requestItem.className = 'new-request-item';
            requestItem.innerHTML = this.renderRequestItem(request);
            requestsList.appendChild(requestItem);
        });
    }
    
    updateCommunityMembersUI(members) {
        console.log('Updating community members UI with:', members);
        const membersContainer = document.getElementById('newOnlineUsers');
        const membersCount = document.getElementById('newOnlineCount');
        
        console.log('Members container:', membersContainer);
        console.log('Members count element:', membersCount);
        
        if (!membersContainer || !membersCount) return;
        
        // Update count
        membersCount.textContent = members.length;
        
        // Clear existing members
        membersContainer.innerHTML = '';
        
        if (members.length === 0) {
            membersContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">No community members</div>';
            return;
        }
        
        // Add each member
        members.forEach(member => {
            console.log('Adding member:', member);
            const memberItem = document.createElement('div');
            memberItem.className = 'new-user-item';
            memberItem.setAttribute('data-user-id', member.id);
            
            // Show admin status on the right side of the username
            const adminStatus = member.is_admin ? '<span class="admin-status">Team TradersEscape</span>' : '';
            
            // Show "You" for the current user, otherwise show the member name
            const displayName = (window.userData && member.id == window.userData.id) ? 'You' : member.name;
            
            memberItem.innerHTML = `
                <div class="new-user-avatar" style="background-color: ${member.color}">
                    ${displayName.charAt(0).toUpperCase()}
                    <div class="new-user-status offline"></div>
                </div>
                <div class="new-user-info">
                    <div class="new-user-name-container">
                        <div class="new-user-name">${displayName}</div>
                        ${adminStatus}
                    </div>
                </div>
            `;
            
            // Add click event for admins to manage users (but not for other admins)
            if (window.userData && window.userData.is_admin && !member.is_admin) {
                memberItem.style.cursor = 'pointer';
                memberItem.addEventListener('click', () => {
                    showUserManagementModal(member);
                });
            }
            
            membersContainer.appendChild(memberItem);
        });
        
        console.log('Community members UI updated successfully');
    }
    
    // Update online status for a specific user
    updateUserOnlineStatus(userId, isOnline) {
        console.log(`Updating user ${userId} status to ${isOnline ? 'online' : 'offline'}`);
        const memberItem = document.querySelector(`[data-user-id="${userId}"]`);
        console.log('Found member item:', memberItem);
        
        if (memberItem) {
            const statusElement = memberItem.querySelector('.new-user-status');
            const statusTextElement = memberItem.querySelector('.new-user-last-seen');
            
            console.log('Status element:', statusElement);
            console.log('Status text element:', statusTextElement);
            
            if (statusElement && statusTextElement) {
                if (isOnline) {
                    statusElement.className = 'new-user-status online';
                    statusTextElement.textContent = 'Online now';
                } else {
                    statusElement.className = 'new-user-status offline';
                    statusTextElement.textContent = 'Community member';
                }
                console.log(`Updated user ${userId} status successfully`);
            }
        } else {
            console.log(`User ${userId} not found in community members list`);
        }
    }
    
    renderRequestItem(request) {
        const name = request.username || request.full_name || 'Unknown';
        const message = request.request_message || 'No message';
        
        // Since we only get pending requests now, always show approve/reject buttons
        const actionsHtml = `
            <div class="new-request-actions">
                <button class="new-request-btn approve" onclick="handleRequestAction(${request.id}, 'approve')">Approve</button>
                <button class="new-request-btn reject" onclick="handleRequestAction(${request.id}, 'reject')">Reject</button>
            </div>
        `;
        
        return `
            <div class="new-request-header">
                <div class="new-request-user">${name}</div>
                <div class="new-request-status pending">pending</div>
            </div>
            ${actionsHtml}
        `;
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
        onlineUsersContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">No community members</div>';
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
    
    // Show/hide delete option based on admin status
    const deleteOption = document.getElementById('deleteMessageOption');
    if (window.userData && window.userData.is_admin) {
        deleteOption.style.display = 'flex';
    } else {
        deleteOption.style.display = 'none';
    }
    
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

function deleteMessage() {
    if (!currentContextMessage) {
        console.log('No current context message');
        return;
    }
    
    // Check if user is admin
    if (!window.userData || !window.userData.is_admin) {
        console.log('Only admins can delete messages');
        return;
    }
    
    const messageId = currentContextMessage.id;
    const messageData = currentContextMessage.data;
    
    console.log('Attempting to delete message:', messageId);
    console.log('Message data:', messageData);
    
    // Hide context menu first
    hideContextMenu();
    
    // Show custom confirmation modal
    showDeleteConfirmationModal();
}

function showDeleteConfirmationModal() {
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus on the modal for accessibility
        const modalContent = modal.querySelector('.confirmation-modal-content');
        if (modalContent) {
            setTimeout(() => modalContent.focus(), 100);
        }
    }
}

function hideDeleteConfirmationModal() {
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function confirmDeleteMessage() {
    if (!currentContextMessage) {
        console.log('No current context message');
        return;
    }
    
    const messageId = currentContextMessage.id;
    const messageData = currentContextMessage.data;
    
    // Hide confirmation modal
    hideDeleteConfirmationModal();
    
    // Update message to show deletion indicator immediately for the admin
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        console.log('Updating message to show deletion for admin');
        
        // Update the message content to show deletion indicator
        const messageContent = messageElement.querySelector('.message-content');
        if (messageContent) {
            // Check if this is the admin's own message
            const isOwnMessage = messageElement.classList.contains('own');
            const deletionText = isOwnMessage 
                ? `Your message was deleted by ${window.userData.username}`
                : `This message was deleted by ${window.userData.username}`;
            
            messageContent.innerHTML = `
                <div class="deleted-message">
                    <i class="bi bi-trash" style="color: #dc2626; margin-right: 8px;"></i>
                    <span style="color: #6b7280; font-style: italic;">${deletionText}</span>
                </div>
            `;
        }
        
        // Add deleted class for styling and ensure consistent appearance
        messageElement.classList.add('message-deleted');
        // Keep 'own' class for positioning but override background color
        messageElement.style.background = '#f3f4f6 !important';
        messageElement.style.borderRadius = '12px';
        messageElement.style.border = 'none';
        messageElement.style.margin = '8px 0';
        
        // Remove any file attachments
        const fileAttachments = messageElement.querySelector('.file-attachment');
        if (fileAttachments) {
            fileAttachments.remove();
        }
        
        // Remove any reactions
        const reactionsContainer = messageElement.querySelector('.emoji-reactions');
        if (reactionsContainer) {
            reactionsContainer.remove();
        }
        
        // Remove reply preview if exists
        const replyPreview = messageElement.querySelector('.reply-preview');
        if (replyPreview) {
            replyPreview.remove();
        }
        
    } else {
        console.log('Message element not found in DOM for admin');
    }
    
    // Send delete request to server via socket
    if (window.chatInstance && window.chatInstance.socket) {
        console.log('Sending delete request to server');
        window.chatInstance.socket.emit('delete_message', {
            messageId: messageId,
            adminId: window.userData.id
        });
    } else {
        console.log('Socket not available');
    }
    
    console.log('Delete request sent for message:', messageId);
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
    
    // Image Modal Functions - moved outside DOMContentLoaded for global access
});

// Image Modal Functions - Global functions for onclick handlers
function openImageModal(imageUrl, imageName) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('imageModalImg');
    const modalHeader = document.getElementById('imageModalHeader');
    
    modalImg.src = imageUrl;
    modalHeader.textContent = imageName || 'Image';
    modal.classList.add('show');
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('show');
    
    // Restore body scroll
    document.body.style.overflow = 'auto';
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

// Go to Home function
function goToHome() {
    window.location.href = 'index.php';
}

function leaveChat() {
    // Show the custom modal
    const modal = document.getElementById('leaveChatModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeLeaveChatModal() {
    // Hide the modal
    const modal = document.getElementById('leaveChatModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

async function confirmLeaveChat() {
    try {
        // Call API to remove community access
        const response = await fetch('leave_chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Disconnect from socket if connected
            if (window.chatInstance && window.chatInstance.socket) {
                window.chatInstance.socket.disconnect();
            }
            
            // Clear any stored chat data
            localStorage.removeItem('chat_current_user');
            localStorage.removeItem('chat_replies');
            
            // Redirect to home page immediately (no alert message)
            window.location.href = 'index.php';
        } else {
            // Show error message with debug info if available
            const errorMessage = data.debug ? `${data.message} (${data.debug})` : data.message;
            showNotification(errorMessage, 'error');
            console.error('Leave chat error:', data);
        }
    } catch (error) {
        console.error('Error leaving chat:', error);
        showNotification('An error occurred while leaving the chat', 'error');
    }
}

// Add event listeners for modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('leaveChatModal');
            const confirmationModal = document.getElementById('confirmationModal');
            const userManagementModal = document.getElementById('userManagementModal');

            // Close modal when clicking outside
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeLeaveChatModal();
                    }
                });
            }

            // Close confirmation modal when clicking outside
            if (confirmationModal) {
                confirmationModal.addEventListener('click', function(e) {
                    if (e.target === confirmationModal) {
                        hideConfirmationModal();
                    }
                });
            }

            // Close user management modal when clicking outside
            if (userManagementModal) {
                userManagementModal.addEventListener('click', function(e) {
                    if (e.target === userManagementModal) {
                        hideUserManagementModal();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('leaveChatModal');
                    const confirmationModal = document.getElementById('confirmationModal');
                    const userManagementModal = document.getElementById('userManagementModal');

                    if (modal && modal.style.display === 'flex') {
                        closeLeaveChatModal();
                    } else if (confirmationModal && confirmationModal.style.display === 'flex') {
                        hideConfirmationModal();
                    } else if (userManagementModal && userManagementModal.style.display === 'flex') {
                        hideUserManagementModal();
                    }
                }
            });
        });

// Handle community request actions
function handleRequestAction(id, action) {
    // Show confirmation modal
    showConfirmationModal(id, action);
}

// Show confirmation modal
function showConfirmationModal(id, action) {
    const modal = document.getElementById('confirmationModal');
    const title = document.getElementById('confirmationTitle');
    const message = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    // Set title and message based on action
    if (action === 'approve') {
        title.textContent = 'Approve Request';
        message.textContent = 'Are you sure you want to approve this community request?';
        confirmBtn.textContent = 'Approve';
        confirmBtn.className = 'confirmation-btn confirm';
    } else if (action === 'reject') {
        title.textContent = 'Reject Request';
        message.textContent = 'Are you sure you want to reject this community request?';
        confirmBtn.textContent = 'Reject';
        confirmBtn.className = 'confirmation-btn confirm danger';
    } else if (action === 'remove') {
        title.textContent = 'Remove User';
        message.textContent = 'Are you sure you want to remove this user from the community?';
        confirmBtn.textContent = 'Remove';
        confirmBtn.className = 'confirmation-btn confirm danger';
    }
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Set up confirm button action
    confirmBtn.onclick = () => {
        hideConfirmationModal();
        executeRequestAction(id, action);
    };
}

// Hide confirmation modal
function hideConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// User Management Modal Functions
let currentManagedUser = null;

// Show user management modal
function showUserManagementModal(user) {
    currentManagedUser = user;
    const modal = document.getElementById('userManagementModal');
    const avatar = document.getElementById('userManagementAvatar');
    const name = document.getElementById('userManagementName');
    
    // Set user info
    avatar.style.backgroundColor = user.color;
    avatar.textContent = user.name.charAt(0).toUpperCase();
    name.textContent = user.name;
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Make functions globally accessible
window.showUserManagementModal = showUserManagementModal;
window.hideUserManagementModal = hideUserManagementModal;
window.makeUserAdmin = makeUserAdmin;
window.removeUser = removeUser;

// Hide user management modal
function hideUserManagementModal() {
    const modal = document.getElementById('userManagementModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentManagedUser = null;
}

// Make user admin
async function makeUserAdmin() {
    if (!currentManagedUser) return;
    
    console.log('Making user admin:', currentManagedUser);
    
    try {
        const requestData = {
            user_id: currentManagedUser.id,
            username: currentManagedUser.name,
            email: currentManagedUser.email
        };
        
        console.log('Sending request data:', requestData);
        
        const response = await fetch('make_user_admin_temp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        const data = JSON.parse(responseText);
        
        if (data.success) {
            hideUserManagementModal();
            // Reload community members to show updated status
            if (window.chatInstance) {
                window.chatInstance.loadCommunityMembers();
            }
            // No notification - silent admin promotion
        } else {
            console.error('Make admin failed:', data);
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error making user admin:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Remove user
async function removeUser() {
    if (!currentManagedUser) return;
    
    // Remove user directly without confirmation
    await executeRemoveUser(currentManagedUser.id);
}

// Execute remove user action
async function executeRemoveUser(userId) {
    try {
        const response = await fetch('remove_user_from_community.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideUserManagementModal();
            // Reload community members to remove the user
            if (window.chatInstance) {
                window.chatInstance.loadCommunityMembers();
            }
            // No notification - direct removal without feedback
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error removing user:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Execute the actual request action
async function executeRequestAction(id, action) {
    try {
        if (action === 'remove_user') {
            // Handle user removal from community members (direct removal, no confirmation)
            await executeRemoveUser(id);
            return;
        }
        
        const response = await fetch('handle_community_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                id: id
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload community requests
            if (window.chatInstance) {
                window.chatInstance.loadCommunityRequests();
                // Also reload community members when someone is approved
                window.chatInstance.loadCommunityMembers();
            }
            
            // No alert message - just reload the data
        } else {
            // Only show error messages for actual errors
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error handling request action:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
        animation: slideInRight 0.3s ease-out;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
    } else if (type === 'error') {
        notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
    } else {
        notification.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<!-- Image Modal -->
<div class="image-modal" id="imageModal" onclick="closeImageModal()">
    <div class="image-modal-content" onclick="event.stopPropagation()">
        <div class="image-modal-close" onclick="closeImageModal()">&times;</div>
        <div class="image-modal-header" id="imageModalHeader"></div>
        <img id="imageModalImg" src="" alt="">
    </div>
</div>

