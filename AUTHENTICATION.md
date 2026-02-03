# Authentication Documentation

## OAuth 2.0 Only - No Password Authentication

This application **exclusively uses OAuth 2.0** authentication via Wikimedia Commons. There is **NO username/password authentication** system.

### Key Points

- ✅ **OAuth 2.0 Only**: Users authenticate via Wikimedia Commons OAuth
- ✅ **No Local Passwords**: No password fields in database
- ✅ **No Registration Forms**: Users are auto-created on first OAuth login
- ✅ **Secure Token Storage**: OAuth tokens encrypted with AES-256-CBC

### Authentication Flow

1. **User clicks "Login"** → Redirected to Wikimedia Commons OAuth
2. **User authorizes** → Wikimedia redirects back with authorization code
3. **App exchanges code** → Gets access token from Wikimedia
4. **App fetches user info** → Gets username, Wikimedia ID, avatar
5. **User is authenticated** → Session created, user stored in database

### Database Schema

The `users` table contains:
- `id` - Auto-incrementing primary key
- `wikimedia_id` - Unique Wikimedia user identifier (used for authentication)
- `username` - Wikimedia username
- `role` - User role (user, conference_admin, super_admin)
- `avatar` - User avatar URL
- `registered_at` - First login timestamp
- `last_login` - Last login timestamp

**Note**: There is NO `password` field in the database.

### OAuth Token Storage

OAuth tokens are stored in the `oauth_tokens` table:
- Tokens are **encrypted** before storage using AES-256-CBC
- Access tokens and refresh tokens are stored separately
- Tokens have expiration timestamps
- Old tokens are automatically cleaned up

### Configuration

OAuth settings are configured in `.env` or `LocalSettings.php`:

```bash
# .env
OAUTH_CLIENT_ID=your_client_id_here
OAUTH_CLIENT_SECRET=your_client_secret_here
OAUTH_REDIRECT_URI=https://yourdomain.com/auth/callback
```

Or in `LocalSettings.php`:

```php
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = 'your_client_id';
$GLOBALS['wgCommonsUploader']['oauth_client_secret'] = 'your_secret';
$GLOBALS['wgCommonsUploader']['oauth_redirect_uri'] = 'https://yourdomain.com/auth/callback';
```

### Obtaining OAuth Credentials

1. Go to: https://meta.wikimedia.org/wiki/Special:OAuthConsumerRegistration
2. Register your application
3. Request these OAuth 2.0 grants:
   - `basic` - Basic user information
   - `profile` - User profile information
   - `highvolume` - High volume editing (for Commons uploads)
   - `editpage` - Edit existing pages
   - `createeditmovepage` - Create, edit, and move pages

### API Endpoints

#### Public (No Authentication)
- `GET /` - Landing page
- `GET /public/landing` - Public landing page

#### Authentication Required
- `GET /auth/login` - Initiate OAuth flow
- `GET /auth/callback` - OAuth callback (auto-called by Wikimedia)
- `POST /auth/logout` - Logout current user
- `GET /auth/me` - Get current user information

All other endpoints require authentication via session.

### Security Features

1. **State Parameter**: CSRF protection during OAuth flow
2. **Encrypted Tokens**: All OAuth tokens encrypted at rest
3. **Session-based Auth**: PHP sessions for user state
4. **No Password Storage**: Eliminates password-related vulnerabilities
5. **Token Expiration**: Automatic token expiration and cleanup

### Why OAuth Only?

Using OAuth 2.0 exclusively provides several benefits:

1. **Security**: No password storage = no password breaches
2. **Trust**: Users authenticate with their existing Wikimedia account
3. **Single Sign-On**: Seamless experience for Wikimedia users
4. **Reduced Maintenance**: No password reset, email verification, etc.
5. **Compliance**: Leverages Wikimedia's security practices

### Troubleshooting

#### "Invalid OAuth credentials"
- Verify `OAUTH_CLIENT_ID` and `OAUTH_CLIENT_SECRET` are correct
- Ensure redirect URI matches exactly (including http/https)
- Check OAuth application is approved on Meta Wikimedia

#### "User not authenticated"
- User's session may have expired
- OAuth token may have expired
- User needs to login again via `/auth/login`

#### "Forbidden: Insufficient permissions"
- User role doesn't have required permissions
- Contact admin to assign appropriate role

### No Password Recovery

Since this application doesn't use passwords, there is:
- ❌ No "forgot password" functionality
- ❌ No password reset emails
- ❌ No password strength requirements
- ❌ No password change forms

Users authenticate through Wikimedia Commons, so all account security is managed there.

## Framework Version

This application is built on **Laravel 12** (latest stable version), which provides:
- Modern PHP 8.2+ features
- Secure session management
- Built-in CSRF protection
- Encrypted cookie support

**Note**: The issue tracker may reference "Django 6.0" but this is a Laravel PHP application, not Django Python.
