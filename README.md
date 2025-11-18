# CommonsEventUploader

A web application built with **Laravel 12.x** and **PHP 8.2+** for uploading conference-related images to Wikimedia Commons. Features OAuth 2.0 authentication, role-based access control, event management, and comprehensive statistics with a beautiful **custom CSS** interface.

## Features

- **Laravel 12.x Framework** - Modern PHP framework with elegant syntax
- **Composer Only** - No Node.js or build process required
- **Custom CSS** - Beautiful, responsive UI with standalone CSS (no dependencies)
- **MediaWiki-style LocalSettings** - Familiar configuration pattern for MediaWiki users
- **OAuth 2.0 Authentication** with Wikimedia Commons
- **Role-Based Access Control** (user, conference_admin, super_admin)
- **Event Management** - Create and manage conference events
- **Category Management** - Organize uploads with categories
- **File Upload** - Direct upload to Wikimedia Commons
- **Statistics Dashboard** - Track uploads, events, and contributions
- **CSRF Protection** - Secure forms and API endpoints
- **RESTful API** - JSON-based API for all operations

## Requirements

- PHP 8.2 or higher
- MySQL 5.7 or higher
- Composer 2.0+
- Wikimedia OAuth 2.0 credentials

**Note:** No Node.js or npm required! This application uses only Composer and PHP.

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/danielyepezgarces/CommonsConferenceUploader.git
   cd CommonsConferenceUploader
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Edit .env with your database and OAuth credentials
   ```
   
   **Optional - MediaWiki-style configuration:**
   ```bash
   cp LocalSettings.php.example LocalSettings.php
   # Edit LocalSettings.php for deployment-specific settings
   # See LocalSettings.md for complete documentation
   ```

4. **Create database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE commons_uploader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

5. **Run migrations:**
   ```bash
   php migrate.php
   ```

6. **Start the server:**
   ```bash
   php artisan serve
   ```

7. **Open in browser:**
   ```
   http://localhost:8000
   ```

## Configuration Options

### MediaWiki-style LocalSettings.php (Recommended)

For MediaWiki administrators, CommonsEventUploader supports familiar `LocalSettings.php` configuration:

```php
<?php
// LocalSettings.php
$GLOBALS['wgCommonsUploader']['app_name'] = 'My Uploader';
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = 'your_client_id';
$GLOBALS['wgCommonsUploader']['max_upload_size'] = 200 * 1024 * 1024; // 200MB
```

**Benefits:**
- Familiar configuration pattern for MediaWiki users
- Override any setting without modifying `.env` or config files
- PHP-based configuration with full language features
- Git-ignored for security

See [LocalSettings.md](LocalSettings.md) for complete documentation.

### Laravel .env Configuration (Alternative)

You can also use standard Laravel `.env` configuration. See [.env.example](.env.example) for available options.

## OAuth 2.0 Setup

1. Register your application at: https://meta.wikimedia.org/wiki/Special:OAuthConsumerRegistration
2. Request these grants:
   - `basic` - Basic user information
   - `highvolume` - High volume editing
   - `editpage` - Edit existing pages
   - `createeditmovepage` - Create, edit, and move pages
3. Configure in `LocalSettings.php` or `.env`:
   
   **LocalSettings.php:**
   ```php
   $GLOBALS['wgCommonsUploader']['oauth_client_id'] = 'your_client_id';
   $GLOBALS['wgCommonsUploader']['oauth_client_secret'] = 'your_secret';
   ```
   
   **.env:**
   ```
   OAUTH_CLIENT_ID=your_client_id
   OAUTH_CLIENT_SECRET=your_secret
   ```

## Database Schema

### users
- User authentication and profile information
- Roles: user, conference_admin, super_admin

### events
- Conference event details
- Managed by conference_admin and super_admin

### categories
- Wikimedia Commons categories
- Tracks upload counts

### uploads
- File upload records
- Links users, events, and categories

### upload_categories
- Many-to-many relationship between uploads and categories

### oauth_tokens
- Secure token storage (hashed)

## API Endpoints

### Authentication
- `GET /auth/login` - Initiate OAuth login
- `GET /auth/callback` - OAuth callback handler
- `POST /auth/logout` - Logout user
- `GET /auth/me` - Get current user info

### Events
- `GET /events` - List all events
- `GET /events/{id}` - Get event details
- `POST /events` - Create new event (admin only)
- `PUT /events/{id}` - Update event (admin only)
- `DELETE /events/{id}` - Delete event (admin only)
- `GET /events/{id}/stats` - Get event statistics

### Uploads
- `POST /events/{id}/upload` - Upload file to event
- `GET /events/{id}/uploads` - List event uploads
- `GET /uploads/my` - Get current user's uploads

### Categories
- `GET /categories` - List all categories
- `GET /categories/search?q={query}` - Search categories
- `GET /categories/top` - Get top categories
- `GET /categories/{id}` - Get category details
- `POST /categories` - Create category (super_admin only)
- `PUT /categories/{id}` - Update category (super_admin only)
- `DELETE /categories/{id}` - Delete category (super_admin only)

### Statistics
- `GET /stats/user` - Get current user statistics
- `GET /stats/global` - Get global statistics (super_admin only)

## User Roles

### user
- View events
- Upload images to events
- View own statistics

### conference_admin
- All user permissions
- Create and manage own events
- View event statistics

### super_admin
- All permissions
- Manage all events and categories
- Assign user roles
- View global statistics

## Security Features

- **CSRF Protection** - All state-changing requests require CSRF token
- **OAuth 2.0** - Secure authentication with Wikimedia
- **Password-less** - No local password storage
- **Token Hashing** - OAuth tokens stored as SHA-256 hashes
- **Role-Based Access** - Middleware enforces permissions
- **File Validation** - Type and size restrictions
- **SQL Injection Protection** - Prepared statements throughout

## Development

### Running Tests
```bash
# Tests would go here - placeholder for future implementation
```

### Code Style
- Follow PSR-12 coding standards
- Use type hints for all parameters and return values
- Document public methods with PHPDoc

## Tech Stack

- **Framework**: Laravel 12.x
- **Backend**: PHP 8.2+
- **Database**: MySQL with PDO
- **HTTP Client**: GuzzleHTTP
- **Frontend**: Custom CSS, Vanilla JavaScript
- **Build Tool**: None (Composer only)
- **Architecture**: Laravel MVC with Service Layer

## File Structure

```
CommonsConferenceUploader/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Request handlers
│   │   └── Middleware/       # Auth, CSRF middleware
│   ├── Models/               # Database models
│   ├── Services/             # Business logic
│   └── Database.php          # Database connection
├── config/                   # Configuration files
├── database/
│   └── migrations/           # SQL migration files
├── public/
│   └── index.php            # Entry point
├── resources/
│   └── views/               # HTML templates
├── routes/
│   └── api.php              # Route definitions
├── storage/                 # Logs and cache
├── .env.example             # Environment template
├── composer.json            # Dependencies
└── README.md
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For issues and questions, please use the GitHub issue tracker.

## Credits

Developed for the Wikimedia Commons community to facilitate conference image uploads.
