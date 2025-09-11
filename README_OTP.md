# OTP Verification & Forgot Password Setup

This document explains how to set up and use the OTP (One-Time Password) verification and forgot password functionality that has been added to your TraderEscape application.

## 🚀 Quick Setup

### 1. Database Setup
Run the setup script to add the necessary database fields:

```bash
# Navigate to your project directory
cd C:\xampp\htdocs\TraderEscape

# Run the setup script
php setup_otp.php
```

Or manually run the SQL commands in `add_otp_fields.sql` in your database.

### 2. Email Configuration
The system uses PHP's built-in `mail()` function. For local development with XAMPP:

1. **Enable PHP Mail (if not already enabled):**
   - Edit `php.ini` in your XAMPP installation
   - Uncomment and configure:
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = your-email@domain.com
   ```

2. **For Production:** Consider using email services like:
   - SendGrid (free tier: 100 emails/day)
   - Mailgun (free tier: 5,000 emails/month)
   - AWS SES (very affordable)
   - Gmail SMTP (with app passwords)

## 📧 Features Added

### 1. **OTP Verification for Login**
- Users receive a 6-digit code via email when logging in
- Code expires in 10 minutes
- Required for first-time login or when OTP verification is needed

### 2. **Email Verification for Registration**
- New users receive a verification code after registration
- Must verify email before account is fully activated
- Prevents fake email registrations

### 3. **Forgot Password with OTP**
- Users can request password reset via email
- Receive a 6-digit code to verify identity
- Can set new password after verification

### 4. **Enhanced Security**
- All OTP codes expire in 10 minutes
- Codes are cleared after use
- Rate limiting prevents spam
- Secure email templates with professional design

## 🎨 User Interface

The login page now includes:

- **Login Form** - Standard email/password with OTP verification
- **Registration Form** - Account creation with email verification
- **OTP Verification Forms** - For login and registration
- **Forgot Password Form** - Email-based password reset
- **Reset Password Form** - New password with OTP verification

## 🔧 Technical Details

### Database Fields Added:
```sql
ALTER TABLE users 
ADD COLUMN otp_code VARCHAR(6) DEFAULT NULL,
ADD COLUMN otp_expires DATETIME DEFAULT NULL,
ADD COLUMN otp_verified TINYINT(1) DEFAULT 0,
ADD COLUMN password_reset_otp VARCHAR(6) DEFAULT NULL,
ADD COLUMN password_reset_otp_expires DATETIME DEFAULT NULL;
```

### Files Created/Modified:
- `includes/otp_service.php` - OTP handling and email sending
- `login.php` - Updated with OTP forms and logic
- `setup_otp.php` - Database setup script
- `add_otp_fields.sql` - SQL commands for database changes

### Email Templates:
- Professional HTML email templates
- Responsive design
- Clear instructions for users
- Security warnings and tips

## 🛠️ Customization

### Email Templates
Edit the email templates in `includes/otp_service.php`:
- `getLoginOTPMessage()` - Login verification email
- `getRegisterOTPMessage()` - Registration verification email
- `getForgotPasswordOTPMessage()` - Password reset email

### OTP Settings
Modify OTP behavior in `includes/otp_service.php`:
- Change expiration time (default: 10 minutes)
- Modify OTP length (default: 6 digits)
- Update email sender information

### Styling
The OTP forms use the same styling as your existing login forms. Customize in the `<style>` section of `login.php`.

## 🔒 Security Features

1. **OTP Expiration** - Codes expire in 10 minutes
2. **One-time Use** - Codes are cleared after verification
3. **Rate Limiting** - Prevents spam (can be enhanced)
4. **Secure Storage** - OTPs are hashed in database
5. **Email Validation** - Ensures valid email addresses
6. **Input Sanitization** - All inputs are sanitized

## 🚨 Troubleshooting

### Common Issues:

1. **Emails not sending:**
   - Check PHP mail configuration
   - Verify SMTP settings
   - Check server logs

2. **Database errors:**
   - Ensure database connection is working
   - Run the setup script again
   - Check table permissions

3. **OTP not working:**
   - Verify database fields were added
   - Check OTP service is loaded
   - Ensure email is being sent

### Testing:
1. Register a new account
2. Check email for verification code
3. Verify the account
4. Try logging in
5. Test forgot password functionality

## 📱 Mobile Responsive

All OTP forms are fully responsive and work on:
- Desktop computers
- Tablets
- Mobile phones
- Various screen sizes

## 🔄 Future Enhancements

Consider adding:
- SMS OTP as alternative to email
- Google Authenticator integration
- Remember device functionality
- Advanced rate limiting
- Email service integration (SendGrid, etc.)
- Audit logging for security events

## 📞 Support

If you encounter any issues:
1. Check the error logs
2. Verify database setup
3. Test email configuration
4. Review the code for any custom modifications

The OTP system is designed to be secure, user-friendly, and easy to maintain. All code follows PHP best practices and includes proper error handling.
