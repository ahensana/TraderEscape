<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Design Test - TraderEscape</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .auth-container {
            max-width: 480px;
            margin: 0 auto;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ffffff, #f8fafc, #e2e8f0);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-top: 1rem;
        }
        
        .auth-title {
            font-size: 2.75rem;
            font-weight: 800;
            margin: 0 0 0.75rem 0;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        
        .auth-subtitle {
            color: #94a3b8;
            font-size: 1.125rem;
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }
        
        /* OTP Input Styling */
        .otp-input {
            width: 100%;
            padding: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            line-height: 1.2;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .otp-input:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 6px rgba(96, 165, 250, 0.2), 0 12px 40px rgba(0, 0, 0, 0.4);
            transform: translateY(-3px);
        }

        .otp-input::placeholder {
            color: #94a3b8;
            font-size: 1.2rem;
            letter-spacing: 0.3rem;
            font-weight: 400;
        }
        
        /* OTP Info Box */
        .otp-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1));
            border: 2px solid rgba(59, 130, 246, 0.4);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.1);
        }
        
        .otp-info-icon {
            font-size: 3rem;
            color: #60a5fa;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .otp-info-text {
            color: #93c5fd;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }
        
        /* OTP Form Container */
        .otp-form-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 1rem 0;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        /* OTP Label */
        .otp-label {
            display: block;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        
        /* OTP Instructions */
        .otp-instructions {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.25rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            text-transform: none;
            letter-spacing: 0.025em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            color: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #0f172a;
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-full {
            width: 100%;
            padding: 1.5rem 2rem;
            font-size: 1.125rem;
        }
        
        .demo-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .demo-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .demo-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <main class="hero-section">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-title">OTP Design Test</h1>
                <p class="auth-subtitle">Test the new OTP input design</p>
            </div>
            
            <div class="demo-buttons">
                <button class="demo-btn" onclick="showLoginOTP()">Login OTP</button>
                <button class="demo-btn" onclick="showRegisterOTP()">Register OTP</button>
                <button class="demo-btn" onclick="showResetOTP()">Reset OTP</button>
                <button class="demo-btn" onclick="hideAll()">Hide All</button>
            </div>
            
            <!-- Login OTP Form -->
            <div id="loginOTPForm" class="otp-form-container" style="display: none;">
                <div class="otp-info">
                    <div class="otp-info-icon">📧</div>
                    <p class="otp-info-text">We've sent a 6-digit verification code to your email address</p>
                </div>
                
                <div class="form-group">
                    <label for="loginOTP" class="otp-label">Enter Verification Code</label>
                    <input type="text" id="loginOTP" class="otp-input" 
                           placeholder="000000"
                           maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    <div class="otp-instructions">
                        Enter the 6-digit code from your email
                    </div>
                </div>

                <button class="btn btn-primary btn-full">
                    <i class="bi bi-shield-check"></i>
                    <span>Verify & Sign In</span>
                </button>
            </div>
            
            <!-- Register OTP Form -->
            <div id="registerOTPForm" class="otp-form-container" style="display: none;">
                <div class="otp-info">
                    <div class="otp-info-icon">🎉</div>
                    <p class="otp-info-text">Welcome! Please verify your email address to complete registration</p>
                </div>
                
                <div class="form-group">
                    <label for="registerOTP" class="otp-label">Enter Verification Code</label>
                    <input type="text" id="registerOTP" class="otp-input" 
                           placeholder="000000"
                           maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    <div class="otp-instructions">
                        Enter the 6-digit code from your email
                    </div>
                </div>

                <button class="btn btn-primary btn-full">
                    <i class="bi bi-shield-check"></i>
                    <span>Verify Email</span>
                </button>
            </div>
            
            <!-- Reset OTP Form -->
            <div id="resetOTPForm" class="otp-form-container" style="display: none;">
                <div class="otp-info">
                    <div class="otp-info-icon">🔑</div>
                    <p class="otp-info-text">Enter the reset code sent to your email and create a new password</p>
                </div>
                
                <div class="form-group">
                    <label for="resetOTP" class="otp-label">Enter Reset Code</label>
                    <input type="text" id="resetOTP" class="otp-input" 
                           placeholder="000000"
                           maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    <div class="otp-instructions">
                        Enter the 6-digit code from your email
                    </div>
                </div>

                <button class="btn btn-primary btn-full">
                    <i class="bi bi-key"></i>
                    <span>Reset Password</span>
                </button>
            </div>
        </div>
    </main>
    
    <script>
        // OTP input formatting and visual feedback
        document.querySelectorAll('.otp-input').forEach(input => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Add visual feedback for each digit
                if (e.target.value.length > 0) {
                    e.target.style.borderColor = '#10b981';
                    e.target.style.background = 'rgba(16, 185, 129, 0.1)';
                } else {
                    e.target.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    e.target.style.background = 'rgba(255, 255, 255, 0.08)';
                }
                
                // Auto-submit when 6 digits are entered
                if (e.target.value.length === 6) {
                    // Add success animation
                    e.target.style.borderColor = '#10b981';
                    e.target.style.background = 'rgba(16, 185, 129, 0.2)';
                    e.target.style.transform = 'scale(1.05)';
                    
                    setTimeout(() => {
                        alert('OTP entered: ' + e.target.value);
                        e.target.value = '';
                        e.target.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                        e.target.style.background = 'rgba(255, 255, 255, 0.08)';
                        e.target.style.transform = 'scale(1)';
                    }, 800);
                }
            });
            
            // Add focus effects
            input.addEventListener('focus', function(e) {
                e.target.style.transform = 'scale(1.02)';
                e.target.style.boxShadow = '0 0 0 6px rgba(96, 165, 250, 0.3), 0 12px 40px rgba(0, 0, 0, 0.4)';
            });
            
            input.addEventListener('blur', function(e) {
                e.target.style.transform = 'scale(1)';
                e.target.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';
            });
        });
        
        function showOTPFormWithAnimation(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.style.display = 'block';
                form.style.opacity = '0';
                form.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    form.style.transition = 'all 0.5s ease-out';
                    form.style.opacity = '1';
                    form.style.transform = 'translateY(0)';
                }, 100);
                
                // Focus on OTP input
                setTimeout(() => {
                    const otpInput = form.querySelector('.otp-input');
                    if (otpInput) {
                        otpInput.focus();
                    }
                }, 600);
            }
        }
        
        function showLoginOTP() {
            hideAll();
            showOTPFormWithAnimation('loginOTPForm');
        }
        
        function showRegisterOTP() {
            hideAll();
            showOTPFormWithAnimation('registerOTPForm');
        }
        
        function showResetOTP() {
            hideAll();
            showOTPFormWithAnimation('resetOTPForm');
        }
        
        function hideAll() {
            const forms = ['loginOTPForm', 'registerOTPForm', 'resetOTPForm'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.style.display = 'none';
                    form.style.opacity = '0';
                    form.style.transform = 'translateY(30px)';
                }
            });
        }
    </script>
</body>
</html>
