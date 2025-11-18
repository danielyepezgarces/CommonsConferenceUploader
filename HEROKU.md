# Heroku Deployment Guide

Complete guide for deploying CommonsEventUploader to Heroku.

## Prerequisites

- Heroku account
- Heroku CLI installed
- Git repository initialized

## Quick Start

### 1. Create Heroku App

```bash
heroku create your-app-name
```

### 2. Add MySQL Database

```bash
# Using ClearDB (free tier available)
heroku addons:create cleardb:ignite

# Or using JawsDB
heroku addons:create jawsdb:kitefin

# Get database credentials
heroku config:get CLEARDB_DATABASE_URL
```

### 3. Configure Environment Variables

```bash
# Application
heroku config:set APP_NAME="CommonsEventUploader"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=$(php generate-key.php)

# Database (if using ClearDB)
heroku config:set DB_CONNECTION=mysql
heroku config:set DB_HOST=your-db-host
heroku config:set DB_PORT=3306
heroku config:set DB_DATABASE=your-db-name
heroku config:set DB_USERNAME=your-db-username
heroku config:set DB_PASSWORD=your-db-password

# OAuth (Wikimedia Commons)
heroku config:set OAUTH_CLIENT_ID=your-client-id
heroku config:set OAUTH_CLIENT_SECRET=your-client-secret
heroku config:set OAUTH_REDIRECT_URI=https://your-app-name.herokuapp.com/api/auth/callback

# Commons API
heroku config:set COMMONS_API_URL=https://commons.wikimedia.org/w/api.php
```

### 4. Deploy

```bash
git push heroku main
```

### 5. Run Database Migrations

```bash
heroku run php update.php
```

### 6. Setup Scheduled Jobs

Heroku doesn't support traditional cron jobs. Use **Heroku Scheduler** instead:

```bash
# Add Heroku Scheduler (free tier available)
heroku addons:create scheduler:standard

# Open scheduler dashboard
heroku addons:open scheduler
```

Add these jobs in the Heroku Scheduler dashboard:

**Every 10 minutes:**
```
php process-queue.php
```

**Hourly:**
```
php cleanup-files.php --hours=6 --cancelled
```

**Daily:**
```
php update.php
```

## Important: File Storage Policy

### ⚠️ Heroku Ephemeral Filesystem

Heroku uses an **ephemeral filesystem** that is cleared on each dyno restart. This makes it perfect for our use case:

- **Temporary storage only** - Files in `storage/uploads/` are automatically cleared on restart
- **No permanent storage** - Images are never kept after upload to Commons
- **Automatic cleanup** - Dyno restarts provide built-in cleanup

### File Lifecycle

1. **User uploads file** → Saved to `storage/uploads/` temporarily
2. **Queue processor runs** → Uploads to Wikimedia Commons
3. **Upload succeeds** → File deleted immediately from `storage/uploads/`
4. **Upload fails** → Retries up to 3 times, then file deleted
5. **Hourly cleanup** → Removes any orphaned files older than 6 hours

### Disk Space Management

```bash
# Check disk usage
heroku run df -h

# View upload directory size
heroku run du -sh storage/uploads/

# Manual cleanup (if needed)
heroku run php cleanup-files.php --hours=1 --cancelled
```

## Procfile Explained

The `Procfile` tells Heroku how to run your application:

```
web: vendor/bin/heroku-php-apache2 public/
```

This:
- Uses Apache2 web server
- Serves from `public/` directory (Laravel's public folder)
- Handles PHP requests automatically

## Configuration Options

### Option A: Environment Variables (.env style)

Set all configuration via Heroku config vars (recommended for Heroku):

```bash
heroku config:set VARIABLE_NAME=value
```

### Option B: LocalSettings.php (MediaWiki style)

Create a `LocalSettings.php` file in your project:

```php
<?php
// LocalSettings.php
$GLOBALS['wgCommonsUploader']['db_host'] = $_ENV['CLEARDB_DATABASE_URL'] ?? '';
$GLOBALS['wgCommonsUploader']['max_upload_size'] = 100 * 1024 * 1024; // 100MB
```

Then commit and deploy:

```bash
git add LocalSettings.php
git commit -m "Add Heroku LocalSettings"
git push heroku main
```

## Scaling

### Web Dynos

```bash
# Check current dyno usage
heroku ps

# Scale web dynos
heroku ps:scale web=1

# For high traffic (requires paid dyno)
heroku ps:scale web=2
```

### Database

```bash
# Upgrade database (ClearDB example)
heroku addons:upgrade cleardb:punch

# Check database info
heroku addons:info cleardb
```

## Monitoring

### View Logs

```bash
# View real-time logs
heroku logs --tail

# View app logs
heroku logs --source app --tail

# View last 1000 lines
heroku logs -n 1000

# Search logs
heroku logs --tail | grep "ERROR"
```

### Application Metrics

```bash
# View dyno metrics
heroku ps

# View app metrics
heroku run php artisan --version
```

## Troubleshooting

### Issue: Database Connection Failed

```bash
# Check database credentials
heroku config:get CLEARDB_DATABASE_URL

# Test database connection
heroku run php -r "new PDO('mysql:host=...;dbname=...', 'user', 'pass');"
```

### Issue: Upload Failures

```bash
# Check disk space
heroku run df -h

# Check upload directory
heroku run ls -lah storage/uploads/

# Run manual cleanup
heroku run php cleanup-files.php --hours=1
```

### Issue: Scheduled Jobs Not Running

```bash
# Check scheduler
heroku addons:open scheduler

# Check logs for job execution
heroku logs --source scheduler --tail
```

### Issue: Dyno Sleeping (Free Tier)

Free tier dynos sleep after 30 minutes of inactivity:

**Solutions:**
1. Upgrade to Hobby dyno ($7/month) - no sleeping
2. Use external uptime monitor (e.g., UptimeRobot) to ping every 25 minutes
3. Accept the sleep behavior (acceptable for low-traffic apps)

```bash
# Upgrade to Hobby dyno (no sleeping)
heroku ps:resize web=hobby
```

## Cost Optimization

### Free Tier Setup

- **Web Dyno**: Free (sleeps after 30 min)
- **Database**: ClearDB Ignite (free, 5MB)
- **Scheduler**: Free

**Total: $0/month** (with limitations)

### Recommended Production Setup

- **Web Dyno**: Hobby ($7/month) - no sleeping
- **Database**: ClearDB Punch ($10/month) - 1GB
- **Scheduler**: Free

**Total: $17/month** (no limitations)

## Security

### Enable HTTPS

Heroku provides free SSL:

```bash
# SSL is automatic for *.herokuapp.com domains

# For custom domains:
heroku certs:auto:enable
```

### Environment Variable Security

```bash
# Never commit .env or credentials
# Use Heroku config vars instead

# View all config vars
heroku config

# Remove sensitive var
heroku config:unset VARIABLE_NAME
```

## Backup

### Database Backup

```bash
# Create backup
heroku run php -r "exec('mysqldump -h...');"

# Or use ClearDB backup feature (paid plans)
```

### Code Backup

```bash
# Your Git repository IS your backup
git push origin main
git push heroku main
```

## Useful Commands

```bash
# Restart application
heroku restart

# Run one-off commands
heroku run php update.php
heroku run php cleanup-files.php

# Access bash shell
heroku run bash

# View environment
heroku run env

# Check app info
heroku info

# Check releases
heroku releases

# Rollback to previous release
heroku rollback
```

## Additional Resources

- [Heroku PHP Documentation](https://devcenter.heroku.com/categories/php-support)
- [Heroku Scheduler Documentation](https://devcenter.heroku.com/articles/scheduler)
- [ClearDB Documentation](https://devcenter.heroku.com/articles/cleardb)
- [Heroku CLI Documentation](https://devcenter.heroku.com/articles/heroku-cli)

## Support

For issues specific to Heroku deployment:
1. Check Heroku status: https://status.heroku.com/
2. View application logs: `heroku logs --tail`
3. Check dyno status: `heroku ps`
4. Review Heroku documentation

For application-specific issues:
1. Check UPDATE-NOTES.md
2. Check DEPLOYMENT.md
3. Review application logs
