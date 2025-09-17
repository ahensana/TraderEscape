<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "traderescape_db");

$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_admin') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                $error = "All fields are required.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Check if email already exists
                $stmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Email already exists.";
                } else {
                    // Hash password and insert
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $message = "Admin added successfully!";
                    } else {
                        $error = "Error adding admin: " . $mysqli->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Get all admins
$admins = [];
$result = $mysqli->query("SELECT id, username, email, created_at, updated_at FROM admins ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    // Add default values for missing columns
    $row['last_login'] = null; // This column doesn't exist in your table
    $row['is_active'] = 1; // Default to active
    $admins[] = $row;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - The Trader's Escape</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <!-- Trading Background Script -->
    <script src="../assets/trading-background.js" defer></script>
    
    <style>
        /* ===== ESSENTIAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            top: 0 !important;
            height: 100%;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* ===== TRADING BACKGROUND ===== */
        .trading-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            min-height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
            will-change: transform;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background-image: linear-gradient(
                rgba(59, 130, 246, 0.1) 1px,
                transparent 1px
            ),
            linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            background-repeat: repeat;
            opacity: 0.3;
        }

        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
        }

        .bg-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background: radial-gradient(
                circle at 20% 80%,
                rgba(59, 130, 246, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 80% 20%,
                rgba(147, 51, 234, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 40% 40%,
                rgba(16, 185, 129, 0.05) 0%,
                transparent 50%
            );
        }


        /* ===== ADMIN HEADER ===== */
        .admin-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .admin-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-btn {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            color: #60a5fa;
            transform: translateY(-1px);
        }

        .nav-btn.active {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.6);
            color: #60a5fa;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }

        /* ===== MAIN CONTENT ===== */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }

        /* ===== MESSAGES ===== */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* ===== CONTENT GRID ===== */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .content-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59, 130, 246, 0.5),
                transparent
            );
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
            margin-bottom: 20px;
        }

        /* ===== FORM STYLES ===== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 0 20px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-btn {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        /* ===== TABLE STYLES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 12px 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .data-table td {
            padding: 12px 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .data-table tr:hover td {
            color: white;
            background: rgba(59, 130, 246, 0.05);
        }

        .admin-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .admin-header-content {
                flex-direction: column;
                gap: 15px;
            }

            .admin-nav {
                flex-direction: column;
                gap: 10px;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Trading Background -->
    <div class="trading-background">
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
        <div class="bg-glow"></div>
    </div>

    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="The Trader's Escape Logo">
                <h1>Admin Management</h1>
            </div>
            <nav class="admin-nav">
                <a href="admin_dashboard.php" class="nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                    </svg>
                    Dashboard
                </a>
                <a href="community_requests.php" class="nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <path d="M13 8H7"></path>
                        <path d="M17 12H7"></path>
                    </svg>
                    Community Requests
                </a>
                <a href="#" class="logout-btn" onclick="confirmLogout()">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-container">
        <!-- Page Header -->
        <section class="page-header">
            <h2>Admin Management</h2>
            <p>Manage admin accounts and permissions</p>
        </section>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Content Grid -->
        <section class="content-grid">
            <!-- Add Admin Form -->
            <div class="content-card">
                <h3 class="card-title">Add New Admin</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_admin">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            class="form-input" 
                            placeholder="Enter username"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            class="form-input" 
                            placeholder="Enter email address"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input" 
                            placeholder="Enter password (min 6 characters)"
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Confirm password"
                            required
                            minlength="6"
                        >
                    </div>

                    <button type="submit" class="form-btn">Add Admin</button>
                </form>
            </div>

            <!-- Admin List -->
            <div class="content-card">
                <h3 class="card-title">Current Admins</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Last Login</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="admin-avatar">
                                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($admin['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <?php if ($admin['last_login']): ?>
                                        <?php echo date('M j, Y', strtotime($admin['last_login'])); ?>
                                    <?php else: ?>
                                        <span style="color: rgba(255, 255, 255, 0.5);">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $admin['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Logout confirmation function
        function confirmLogout() {
            // Create custom confirmation modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease;
            `;

            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: rgba(15, 23, 42, 0.95);
                border: 1px solid rgba(59, 130, 246, 0.3);
                border-radius: 16px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                animation: slideIn 0.3s ease;
            `;

            modalContent.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fca5a5" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                    <h3 style="color: white; font-size: 1.3rem; font-weight: 600; margin-bottom: 10px;">Confirm Logout</h3>
                    <p style="color: rgba(255, 255, 255, 0.7); font-size: 1rem; line-height: 1.5;">Are you sure you want to logout from the admin panel?</p>
                </div>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="closeLogoutModal()" style="
                        background: rgba(107, 114, 128, 0.2);
                        border: 1px solid rgba(107, 114, 128, 0.3);
                        color: #9ca3af;
                        padding: 10px 20px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.9rem;
                        font-weight: 500;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='rgba(107, 114, 128, 0.3)'" onmouseout="this.style.background='rgba(107, 114, 128, 0.2)'">
                        Cancel
                    </button>
                    <button onclick="proceedLogout()" style="
                        background: rgba(239, 68, 68, 0.2);
                        border: 1px solid rgba(239, 68, 68, 0.3);
                        color: #fca5a5;
                        padding: 10px 20px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.9rem;
                        font-weight: 500;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='rgba(239, 68, 68, 0.3)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.2)'">
                        Logout
                    </button>
                </div>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideIn {
                    from { transform: translateY(-20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeLogoutModal();
                }
            });

            // Close modal with Escape key
            const handleEscape = function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }

        function closeLogoutModal() {
            const modal = document.querySelector('div[style*="position: fixed"]');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        function proceedLogout() {
            // Add loading state
            const logoutBtn = document.querySelector('button[onclick="proceedLogout()"]');
            if (logoutBtn) {
                logoutBtn.innerHTML = '<div class="loading"></div>';
                logoutBtn.disabled = true;
            }
            
            // Redirect to logout page
            setTimeout(() => {
                window.location.href = 'admin_logout.php';
            }, 500);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
