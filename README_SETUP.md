# TraderEscape Platform Setup Guide

## Overview
TraderEscape is a comprehensive trading education platform built with PHP and MySQL. This guide will help you set up the platform with full database functionality.

## Prerequisites
- XAMPP, WAMP, or similar local development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

## Database Setup

### 1. Import Database Schema
1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database called `traderescape_db`
3. Import the `traderescape_db.sql` file to create all tables and sample data

### 2. Configure Database Connection
Edit `config/database.php` and update these values:
```php
define('DB_HOST', 'localhost');     // Your database host
define('DB_NAME', 'traderescape_db'); // Database name
define('DB_USER', 'root');          // Database username
define('DB_PASS', '');              // Database password
```

## File Structure
```
TraderEscape/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── header.php            # Common header
│   ├── footer.php            # Common footer
│   ├── db_functions.php      # Database helper functions
│   └── auth_functions.php    # Authentication functions
├── assets/                   # CSS, JS, and images
├── index.php                 # Homepage
├── about.php                 # About page
├── tools.php                 # Trading tools (requires login)
├── login.php                 # User authentication
├── account.php               # User dashboard (requires login)
├── contact.php               # Contact form
├── disclaimer.php            # Legal disclaimer
├── risk.php                  # Risk disclosure
├── privacy.php               # Privacy policy
├── terms.php                 # Terms of service
├── cookies.php               # Cookies policy
├── check_auth.php            # Authentication check endpoint
├── logout.php                # Logout endpoint
├── test_db.php               # Database test script
└── traderescape_db.sql       # Database schema
```

## Features Implemented

### ✅ Authentication System
- User registration and login
- Session management
- Password hashing
- Authentication middleware

### ✅ Database Integration
- All pages now pull content from database
- Page tracking and analytics
- User activity logging
- Contact form submissions stored in database

### ✅ User Management
- User profiles and statistics
- Learning progress tracking
- Tool usage analytics
- Dashboard with user data

### ✅ Content Management
- Dynamic page content from database
- Trading tools with database integration
- Educational content management
- Site settings management

## Testing the Setup

### 1. Test Database Connection
Visit `http://localhost/TraderEscape/test_db.php` to verify:
- Database connection
- Table creation
- Sample data loading

### 2. Test User Registration
1. Go to `http://localhost/TraderEscape/login.php`
2. Click "Create New Account"
3. Fill out the registration form
4. Verify user is created in database

### 3. Test User Login
1. Use the credentials from registration
2. Login should redirect to tools page
3. Check session is created

### 4. Test Protected Pages
- `tools.php` - Should require login
- `account.php` - Should show user dashboard
- `contact.php` - Should store submissions in database

## Common Issues & Solutions

### Database Connection Failed
- Check XAMPP/WAMP is running
- Verify database credentials in `config/database.php`
- Ensure MySQL service is started

### Pages Show Database Errors
- Check if database exists
- Verify all tables were created
- Check PHP error logs

### Authentication Not Working
- Ensure sessions are enabled
- Check database tables exist
- Verify auth_functions.php is included

### Contact Form Not Working
- Check if `contact_submissions` table exists
- Verify database permissions
- Check PHP error logs

## Security Features

### ✅ Implemented
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- Session-based authentication
- Input validation and sanitization
- CSRF protection (basic)

### 🔒 Recommended Additional Security
- HTTPS enforcement
- Rate limiting for login attempts
- Two-factor authentication
- Regular security audits
- Input validation libraries

## Performance Optimizations

### ✅ Implemented
- Database connection pooling
- Prepared statement caching
- Efficient database queries
- CSS/JS minification ready

### 🚀 Recommended Additional Optimizations
- Redis caching
- CDN integration
- Database query optimization
- Image optimization
- Gzip compression

## Development Notes

### Database Schema
The platform uses a comprehensive database schema with:
- User management tables
- Content management tables
- Analytics and tracking tables
- Educational content tables
- Trading tools tables

### Authentication Flow
1. User registers/logs in via `login.php`
2. Credentials verified against database
3. Session created and stored
4. Protected pages check session validity
5. User data loaded from database

### Page Structure
All pages now use:
- `includes/header.php` for navigation
- `includes/footer.php` for footer
- Database functions for dynamic content
- Consistent styling and layout

## Next Steps

### Immediate
1. Test all functionality
2. Customize content for your needs
3. Set up proper domain and hosting

### Future Enhancements
1. Admin panel for content management
2. Advanced analytics dashboard
3. Email notifications
4. Payment integration
5. Mobile app development

## Support

If you encounter issues:
1. Check the database test page first
2. Review PHP error logs
3. Verify database connectivity
4. Check file permissions
5. Ensure all required files are present

## License
This platform is for educational purposes. Please ensure compliance with local regulations regarding financial education platforms.
