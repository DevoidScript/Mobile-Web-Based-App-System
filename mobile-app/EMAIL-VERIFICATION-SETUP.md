# Email Verification System Setup Guide

This guide will help you set up email verification for your Red Cross Blood Donation System using Gmail SMTP.

## Overview

The email verification system has been integrated into your registration process. When users register, they will:

1. Complete the registration form
2. Receive a verification email with a 6-digit code
3. Enter the code to verify their email
4. Complete the registration process

## Files Added/Modified

### New Files Created:
- `config/email.php` - Email configuration and sending functions
- `templates/email-verification.php` - Email verification page
- `api/email_verification.php` - API endpoints for verification
- `setup-email.php` - Setup script for Gmail configuration

### Modified Files:
- `includes/functions.php` - Added email verification functions
- `api/donor_register.php` - Modified to send verification email
- `api/auth.php` - Modified to check email verification on login

## Database Changes

The following SQL has been run on your Supabase database:

```sql
-- Email verification table
CREATE TABLE email_verifications (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    verification_code VARCHAR(10) NOT NULL,
    user_id UUID NOT NULL,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP WITH TIME ZONE NULL
);

-- Added columns to donor_form table
ALTER TABLE donor_form 
ADD COLUMN email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN email_verified_at TIMESTAMP WITH TIME ZONE NULL;
```

## Gmail Setup Instructions

### Step 1: Enable 2-Factor Authentication
1. Go to your Google Account settings
2. Navigate to Security > 2-Step Verification
3. Enable 2-Step Verification if not already enabled

### Step 2: Generate App Password
1. In Google Account settings, go to Security > 2-Step Verification
2. Scroll down to "App passwords"
3. Click "Select app" and choose "Mail"
4. Click "Select device" and choose "Other (custom name)"
5. Enter "Red Cross Blood Donation System" as the app name
6. Click "Generate"
7. Copy the 16-character password (it will look like: `abcd efgh ijkl mnop`)

### Step 3: Update Configuration
Edit `config/email.php` and update these lines:

```php
define('SMTP_USERNAME', 'your-gmail@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'your-16-character-app-password'); // The app password from step 2
define('SMTP_FROM_EMAIL', 'your-gmail@gmail.com'); // Your Gmail address
define('SMTP_FROM_NAME', 'Red Cross Blood Donation System'); // Your preferred sender name
```

### Step 4: Test Configuration
Run the setup script to test your configuration:

```bash
php setup-email.php
```

## How It Works

### Registration Flow:
1. User fills out registration form
2. System creates user account in Supabase Auth
3. System inserts donor data into `donor_form` table
4. System generates 6-digit verification code
5. System sends verification email via Gmail SMTP
6. User is redirected to email verification page
7. User enters verification code
8. System verifies code and marks email as verified
9. User can now login normally

### Login Flow:
1. User enters email and password
2. System authenticates with Supabase
3. System checks if email is verified
4. If not verified, user is redirected to verification page
5. If verified, user is logged in normally

### Email Verification Features:
- 6-digit numeric codes
- 15-minute expiration time
- Resend functionality
- Countdown timer
- Auto-submit when 6 digits are entered
- Mobile-optimized interface

## Testing the System

### Test Registration:
1. Go to your registration page
2. Fill out the form with a valid email address
3. Submit the form
4. Check your email for the verification code
5. Enter the code on the verification page
6. Verify that you can login after verification

### Test Login Without Verification:
1. Try to login with an unverified account
2. Verify that you're redirected to the verification page
3. Complete verification and try logging in again

## Troubleshooting

### Common Issues:

1. **Emails not being sent**
   - Check Gmail credentials in `config/email.php`
   - Verify App Password is correct
   - Check if 2-Factor Authentication is enabled
   - Run `php setup-email.php` to test configuration

2. **Verification codes not working**
   - Check if codes are expiring (15-minute limit)
   - Verify database connection to Supabase
   - Check if `email_verifications` table exists

3. **Users can't login after verification**
   - Check if `email_verified` column exists in `donor_form` table
   - Verify the verification process completed successfully

### Debug Mode:
To enable debug logging, add this to your PHP error log:

```php
error_log("Email verification debug: " . json_encode($data));
```

## Security Considerations

1. **App Passwords**: Use Gmail App Passwords instead of your main password
2. **Code Expiration**: Verification codes expire in 15 minutes
3. **Rate Limiting**: Consider implementing rate limiting for resend requests
4. **Database Security**: Ensure RLS policies are properly configured

## Customization

### Email Template:
Edit the email template in `config/email.php`:
- Modify `get_verification_email_template()` for HTML content
- Modify `get_verification_email_text()` for plain text content

### Verification Code:
- Change code length in `generate_verification_code()`
- Modify expiration time in `EMAIL_VERIFICATION_EXPIRY_MINUTES`

### Styling:
- Customize the verification page in `templates/email-verification.php`
- Modify CSS for mobile responsiveness

## Support

If you encounter issues:
1. Check the error logs
2. Verify Gmail configuration
3. Test with a simple email first
4. Ensure all database tables exist
5. Check Supabase connection

The system is now ready for production use with proper Gmail configuration!

