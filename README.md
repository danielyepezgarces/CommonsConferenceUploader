# CommonsEventUploader

A web application built with PHP 8.x for uploading conference-related images to Wikimedia Commons. Features OAuth 2.0 authentication, role-based access control, event management, and comprehensive statistics.

## Features

- **OAuth 2.0 Authentication** with Wikimedia Commons
- **Role-Based Access Control** (user, conference_admin, super_admin)
- **Event Management** - Create and manage conference events
- **Category Management** - Organize uploads with categories
- **File Upload** - Direct upload to Wikimedia Commons
- **Statistics Dashboard** - Track uploads, events, and contributions
- **CSRF Protection** - Secure forms and API endpoints
- **RESTful API** - JSON-based API for all operations

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache, Nginx, or PHP built-in server)
- Wikimedia OAuth 2.0 credentials

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/danielyepezgarces/CommonsConferenceUploader.git
   cd CommonsConferenceUploader
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database and OAuth credentials
   ```

4. **Create database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE commons_uploader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

5. **Run migrations:**
   ```bash
   php -r "require 'vendor/autoload.php'; \App\Database::runMigrations();"
   ```

6. **Start the server:**
   ```bash
   php -S localhost:8000 -t public
   ```

7. **Open in browser:**
   ```
   http://localhost:8000
   ```

## OAuth 2.0 Setup

1. Register your application at: https://meta.wikimedia.org/wiki/Special:OAuthConsumerRegistration
2. Request these grants:
   - `basic` - Basic user information
   - `highvolume` - High volume editing
   - `editpage` - Edit existing pages
   - `createeditmovepage` - Create, edit, and move pages
3. Copy the Client ID and Client Secret to your `.env` file

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

- **Backend**: PHP 8.3
- **Database**: MySQL with PDO
- **HTTP Client**: GuzzleHTTP
- **Frontend**: HTML, Tailwind CSS, Vanilla JavaScript
- **Architecture**: MVC with Service Layer

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
