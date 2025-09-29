# 📧 Email Setup Instructions for TraderEscape

## 🚀 Quick Setup Guide

### **Step 1: Install PHPMailer**

```bash
# Navigate to your project directory
cd C:\New Xampp\htdocs\TraderEscape

# Install PHPMailer via Composer
composer install
```

### **Step 2: Configure Gmail SMTP**

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:

   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
   - Copy the 16-character password

3. **Update Email Configuration**:
   - Open `email/config.php`
   - Replace the following values:
   ```php
   define('SMTP_USERNAME', 'your-email@gmail.com'); // Your Gmail
   define('SMTP_PASSWORD', 'your-16-char-app-password'); // Your App Password
   define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // Your Gmail
   ```

### **Step 3: Test the System**

1. **Visit** `forgot_password.php`
2. **Enter a valid email** from your users table
3. **Check your email** for the OTP
4. **Verify** the OTP is received correctly

## 🔧 Configuration Details

### **Gmail SMTP Settings**

- **Host**: smtp.gmail.com
- **Port**: 587
- **Security**: TLS
- **Authentication**: Required

### **Email Limits**

- **Free Gmail**: 500 emails/day
- **Google Workspace**: 2,000 emails/day
- **Rate**: ~100 emails/hour

## 🛠️ Troubleshooting

### **Common Issues**

1. **"Authentication failed"**

   - ✅ Use App Password, not regular password
   - ✅ Enable 2-Factor Authentication first

2. **"Connection refused"**

   - ✅ Check firewall settings
   - ✅ Verify SMTP settings

3. **Emails go to spam**
   - ✅ Gmail SMTP has good deliverability
   - ✅ Consider SPF/DKIM records for custom domain

### **Debug Mode**

To enable email debugging, set in `email/config.php`:

```php
define('SMTP_DEBUG', true);
```

## 📊 Email Template Features

### **HTML Email Includes:**

- ✅ Professional design
- ✅ Responsive layout
- ✅ Clear OTP display
- ✅ Security warnings
- ✅ Branding

### **Plain Text Fallback:**

- ✅ Simple text version
- ✅ Same information
- ✅ Email client compatibility

## 🔒 Security Features

### **OTP Security:**

- ✅ 6-digit random code
- ✅ 5-minute expiration
- ✅ Session-based storage
- ✅ One-time use

### **Email Security:**

- ✅ TLS encryption
- ✅ Secure authentication
- ✅ No sensitive data in logs

## 📈 Production Recommendations

### **For Higher Volume:**

1. **Upgrade to SendGrid/Mailgun**
2. **Implement email queuing**
3. **Add rate limiting**
4. **Monitor delivery rates**

### **For Better Deliverability:**

1. **Set up SPF records**
2. **Configure DKIM**
3. **Use custom domain**
4. **Monitor reputation**

## 🎯 Next Steps

1. **Test thoroughly** with different email providers
2. **Monitor email logs** for any issues
3. **Consider upgrading** to professional email service
4. **Implement email analytics** if needed

---

**Need Help?** Check the error logs in your XAMPP installation for detailed error messages.
