# Deployment Guide

This guide covers deploying CommonsEventUploader to production environments.

## Prerequisites

- PHP 8.1+ with required extensions (pdo, pdo_mysql, json, openssl, fileinfo)
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache 2.4+ or Nginx 1.18+)
- Composer
- SSL/TLS certificate (required for OAuth)
- Wikimedia OAuth 2.0 credentials

## Server Requirements

### PHP Extensions

Required:
- pdo
- pdo_mysql
- json
- openssl
- fileinfo
- mbstring
- curl

Check installed extensions:
```bash
php -m
```

### File Permissions

```bash
# Set ownership
chown -R www-data:www-data /path/to/CommonsConferenceUploader

# Set directory permissions
find /path/to/CommonsConferenceUploader -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/CommonsConferenceUploader -type f -exec chmod 644 {} \;

# Make scripts executable
chmod +x /path/to/CommonsConferenceUploader/migrate.php
chmod +x /path/to/CommonsConferenceUploader/manage-users.php
chmod +x /path/to/CommonsConferenceUploader/generate-key.php

# Ensure storage is writable
chmod -R 775 /path/to/CommonsConferenceUploader/storage
```

## Deployment Steps

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/danielyepezgarces/CommonsConferenceUploader.git
cd CommonsConferenceUploader
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php generate-key.php

# Edit .env with production values
nano .env
```

Required environment variables:
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=generated_key_here

DB_HOST=localhost
DB_DATABASE=commons_uploader
DB_USERNAME=db_user
DB_PASSWORD=secure_password

OAUTH_CLIENT_ID=your_wikimedia_client_id
OAUTH_CLIENT_SECRET=your_wikimedia_client_secret
OAUTH_REDIRECT_URI=https://your-domain.com/auth/callback
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE commons_uploader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create database user
mysql -u root -p -e "CREATE USER 'commons_user'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON commons_uploader.* TO 'commons_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Run migrations
php migrate.php
```

### 5. Web Server Configuration

#### Apache

Create virtual host configuration:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAdmin admin@your-domain.com
    DocumentRoot /var/www/CommonsConferenceUploader/public

    <Directory /var/www/CommonsConferenceUploader/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/commons-error.log
    CustomLog ${APACHE_LOG_DIR}/commons-access.log combined

    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAdmin admin@your-domain.com
    DocumentRoot /var/www/CommonsConferenceUploader/public

    <Directory /var/www/CommonsConferenceUploader/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    SSLCertificateChainFile /path/to/chain.pem

    ErrorLog ${APACHE_LOG_DIR}/commons-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/commons-ssl-access.log combined
</VirtualHost>
```

Enable required modules and restart:
```bash
a2enmod rewrite
a2enmod ssl
systemctl restart apache2
```

#### Nginx

Create server block:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/CommonsConferenceUploader/public;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
```

Restart Nginx:
```bash
systemctl restart nginx
```

### 6. SSL/TLS Certificate

Using Let's Encrypt:
```bash
# Install certbot
apt-get install certbot python3-certbot-apache  # For Apache
apt-get install certbot python3-certbot-nginx   # For Nginx

# Obtain certificate
certbot --apache -d your-domain.com  # For Apache
certbot --nginx -d your-domain.com   # For Nginx
```

### 7. Wikimedia OAuth Setup

1. Go to: https://meta.wikimedia.org/wiki/Special:OAuthConsumerRegistration
2. Select "OAuth 2.0"
3. Fill in application details:
   - Application name: CommonsEventUploader
   - OAuth callback URL: https://your-domain.com/auth/callback
   - Applicable grants: Select required permissions
4. Copy Client ID and Client Secret to .env

### 8. Create First Admin User

After first user logs in via OAuth:
```bash
php manage-users.php list-users
php manage-users.php promote-user <wikimedia_id> super_admin
```

## Post-Deployment

### Security Checklist

- [ ] HTTPS enabled and enforced
- [ ] APP_DEBUG=false in production
- [ ] Strong database password set
- [ ] File permissions properly configured
- [ ] .env file not publicly accessible
- [ ] Error logs configured and monitored
- [ ] Firewall rules configured
- [ ] Database backups scheduled

### Monitoring

1. **Error Logs**
   ```bash
   tail -f /var/log/apache2/commons-error.log  # Apache
   tail -f /var/log/nginx/error.log            # Nginx
   tail -f storage/logs/*.log                   # Application logs
   ```

2. **Performance**
   - Monitor PHP-FPM status
   - Track database query performance
   - Monitor disk space usage

3. **Security**
   - Review authentication logs
   - Monitor failed login attempts
   - Check for suspicious file uploads

### Backup Strategy

1. **Database Backup**
   ```bash
   # Daily backup script
   #!/bin/bash
   DATE=$(date +%Y%m%d)
   mysqldump -u commons_user -p commons_uploader > /backups/db_$DATE.sql
   gzip /backups/db_$DATE.sql
   ```

2. **File Backup**
   ```bash
   # Backup uploaded files and configuration
   tar -czf /backups/files_$DATE.tar.gz /var/www/CommonsConferenceUploader/storage
   ```

3. **Automated Backups**
   ```bash
   # Add to crontab
   0 2 * * * /path/to/backup-script.sh
   ```

### Updates

```bash
# Pull latest changes
cd /var/www/CommonsConferenceUploader
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php migrate.php

# Clear cache if needed
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check PHP error logs
   - Verify file permissions
   - Ensure .htaccess is readable
   - Check PHP extensions are installed

2. **Database Connection Failed**
   - Verify database credentials in .env
   - Check database server is running
   - Ensure database user has proper permissions

3. **OAuth Callback Issues**
   - Verify OAUTH_REDIRECT_URI matches registered URL
   - Ensure HTTPS is enabled
   - Check OAuth credentials

4. **File Upload Fails**
   - Check PHP upload_max_filesize setting
   - Verify post_max_size setting
   - Check storage permissions
   - Review Commons API credentials

## Performance Optimization

1. **PHP OPcache**
   ```ini
   ; php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   opcache.validate_timestamps=0  # Production only
   ```

2. **Database Optimization**
   - Add indexes for frequently queried columns
   - Enable query caching
   - Optimize slow queries

3. **Caching**
   - Consider implementing Redis for session storage
   - Cache database query results
   - Use CDN for static assets

## Support

For deployment issues:
- Check application logs in storage/logs/
- Review web server error logs
- Consult the GitHub issue tracker
- Review Wikimedia OAuth documentation

## Maintenance

### Regular Tasks

- Update dependencies monthly
- Review security advisories
- Monitor disk space
- Backup verification
- Log rotation
- SSL certificate renewal

### Scheduled Maintenance

- Database optimization (monthly)
- Clean old logs (weekly)
- Security updates (as needed)
- Performance review (quarterly)
