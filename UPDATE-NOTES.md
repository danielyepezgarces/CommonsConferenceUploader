# CommonsEventUploader - Project Update Notes

## Overview

This document summarizes the major updates implemented in this release, including copyright changes, new features, and system improvements.

---

## 1. Copyright & Licensing ✅

### Changes Made
- **License**: Migrated from MIT to **GNU General Public License v3.0** (GPLv3)
- **Copyright Holder**: Daniel Yepez Garces
- **Copyright Headers**: Added to all 20+ PHP files in the project

### Files Updated
- `LICENSE` - Full GPLv3 license text
- All PHP files in `app/` directory
- All utility scripts (`migrate.php`, `manage-users.php`, `generate-key.php`, `update.php`, `process-queue.php`, `artisan`)
- All view templates

### Branding Changes
- Removed "Powered by Laravel" text from all pages
- Replaced with: "CommonsEventUploader © 2025 Daniel Yepez Garces • Licensed under GPLv3"

---

## 2. Conference Feature ✅

### Database Schema
**New Table**: `conferences`
- Tracks conference information separate from events
- Fields: name, slug, description, dates, location, website_url, logo_url, wikidata_id, organizer details
- Status management: draft, published, archived

**Updated Table**: `events`
- Added `conference_id` foreign key
- Events can now belong to conferences

### Backend Implementation
**Model**: `app/Models/Conference.php` (234 lines)
- Full CRUD operations
- Relationship: Conference has many Events
- Statistics: event count, upload count, contributor count
- Slug generation for clean URLs
- Status filtering (draft/published/archived)

**Controller**: `app/Http/Controllers/ConferenceController.php` (231 lines)
- Role-based permissions (conference_admin can create, super_admin manages all)
- Full CRUD API endpoints
- Statistics endpoint

### API Endpoints
```
GET    /api/conferences              - List conferences (with status filter)
GET    /api/conferences/{id}         - Get conference with associated events
POST   /api/conferences              - Create conference (conference_admin+)
PUT    /api/conferences/{id}         - Update conference (owner or super_admin)
DELETE /api/conferences/{id}         - Delete conference (super_admin only)
GET    /api/conferences/{id}/stats   - Conference statistics
```

### Landing Page Integration
- PublicController updated to show recent published conferences
- Displays conference stats (events, uploads, participants)
- JSON API endpoint for frontend consumption

### Permissions
- **user**: Can view published conferences
- **conference_admin**: Can create and manage own conferences
- **super_admin**: Can manage all conferences and change any status

---

## 3. MediaWiki-style System Updater ✅

### Core System
**Class**: `app/Updater/SystemUpdater.php` (259 lines)
- Automatic database schema updates
- Migration tracking and versioning
- Transaction support with rollback on failure

**Script**: `update.php` (CLI interface)
- Command-line tool for running updates
- Colored terminal output
- Dry-run mode for safe preview

### Features
1. **Automatic Table Creation**
   - Creates `system_updates` tracking table if missing
   - Executes pending migrations automatically

2. **Versioned Migrations**
   - Sequential SQL file execution (001, 002, 003, etc.)
   - Never applies the same migration twice
   - Tracks completion timestamp

3. **Migration Tracking**
   - Records each applied migration in database
   - Stores schema version number
   - Maintains application history

4. **Safe Operations**
   - Dry-run mode: `php update.php --dry-run`
   - Transaction support - rollback on failure
   - Detailed logging of all operations

### Usage
```bash
# Run all pending updates
php update.php

# Preview changes without applying
php update.php --dry-run
```

### How It Works
1. Scans `database/migrations/` for `.sql` files
2. Checks `system_updates` table for applied migrations
3. Applies pending migrations in sequential order
4. Records each successful migration
5. Updates schema version

### Backward Compatibility
- Existing data always preserved
- New columns added with safe defaults
- Foreign keys use SET NULL for safety
- No destructive operations without explicit confirmation

---

## 4. Scheduling System for Media Publishing ✅

### Database Schema
**New Tables**:
1. `scheduled_uploads` - Queue management for photos/videos
2. `publish_logs` - Detailed action logging with timestamps

### Features
**Publish Types**:
- **Immediate Publishing**: Upload processed as soon as possible
- **Scheduled Publishing**: Upload at specific future timestamp

**Status Lifecycle**:
```
pending → processing → completed
                    → failed (retry up to 3 attempts)
                    → cancelled (by user)
```

### Backend Implementation
**Model**: `app/Models/ScheduledUpload.php` (241 lines)
- Queue entry management
- Status tracking
- Retry logic with configurable max attempts
- File type support (photo/video)
- Integrated logging

**Controller**: `app/Http/Controllers/ScheduleController.php` (286 lines)
- Upload scheduling API
- Queue management
- User-specific views
- Cancel functionality

**Service**: `app/Services/ScheduleService.php` (165 lines)
- Batch processing engine
- Automatic retry on failure
- Commons API integration
- Error handling and logging

### API Endpoints
```
POST   /api/schedule/upload          - Schedule immediate or future upload
GET    /api/schedule/my              - List user's scheduled uploads
GET    /api/schedule/{id}            - Get upload details with logs
POST   /api/schedule/{id}/cancel     - Cancel pending/failed uploads
GET    /api/schedule/queue/status    - Get queue statistics
```

### Queue Processor
**Script**: `process-queue.php` (CLI tool)
- Batch processing: `--batch-size=N`
- Daemon mode: `--daemon` (continuous processing)
- Colored output with timestamps
- Error tracking and reporting

**Usage**:
```bash
# One-time processing
php process-queue.php

# Process with custom batch size
php process-queue.php --batch-size=20

# Daemon mode (continuous)
php process-queue.php --daemon
```

### Cron Job Setup
See `cron-example.txt` for configuration examples:
```cron
# Process queue every 5 minutes
*/5 * * * * cd /path/to/app && php process-queue.php >> storage/logs/queue.log 2>&1
```

### Key Features
1. **Queue Management**
   - Automatic processing of immediate uploads
   - Time-based processing of scheduled uploads
   - Failed upload retry logic (up to 3 attempts)
   - Status tracking throughout lifecycle

2. **Comprehensive Logging**
   - Every action logged to `publish_logs` table
   - Actions: scheduled, processing, completed, failed, cancelled, retrying
   - JSON metadata support for detailed tracking
   - Timestamp tracking for all stages

3. **Security**
   - Users can only schedule uploads for their events
   - File validation (type, size, MIME)
   - Proper authentication and authorization
   - Secure file storage in `storage/uploads/`

4. **Flexibility**
   - Configurable batch sizes
   - Configurable retry attempts
   - Support for both photos and videos
   - Immediate or scheduled publishing

---

## 5. Architecture & Code Quality ✅

### Standards Maintained
- ✅ Consistent with existing codebase patterns
- ✅ GPLv3 license compliance
- ✅ Security best practices followed
- ✅ No Node.js dependencies (Composer only)
- ✅ PHP 8.2+ compatibility
- ✅ PSR-4 autoloading

### Code Statistics
**New Code Added**:
- Conference Model: 234 lines
- Conference Controller: 231 lines
- System Updater: 259 lines
- Scheduled Upload Model: 241 lines
- Schedule Controller: 286 lines
- Schedule Service: 165 lines
- **Total New PHP Code**: ~1,416 lines

**New Database Tables**: 3
- conferences
- scheduled_uploads
- publish_logs

**New Database Columns**: 1
- events.conference_id

**New API Endpoints**: 12
- 6 conference endpoints
- 5 schedule endpoints  
- 1 queue status endpoint

### Documentation Updated
- ✅ LICENSE file (MIT → GPLv3)
- ✅ README.md (new features section)
- ✅ API.md (new endpoints documented)
- ✅ UPDATE-NOTES.md (this file)
- ✅ cron-example.txt (cron job examples)

---

## Migration Guide

### For Existing Installations

1. **Backup Database**
   ```bash
   mysqldump -u user -p database > backup.sql
   ```

2. **Pull Latest Code**
   ```bash
   git pull origin main
   ```

3. **Run System Updater**
   ```bash
   php update.php
   ```
   This will:
   - Create `system_updates` tracking table
   - Apply new migrations (007, 008, 009, 010)
   - Update schema version

4. **Setup Cron Job** (for scheduling system)
   ```bash
   crontab -e
   # Add: */5 * * * * cd /path/to/app && php process-queue.php
   ```

5. **Verify Installation**
   ```bash
   # Check database tables
   mysql> SHOW TABLES;
   
   # Test API endpoints
   curl http://localhost:8000/api/conferences
   ```

### For New Installations

1. **Clone Repository**
   ```bash
   git clone https://github.com/danielyepezgarces/CommonsConferenceUploader.git
   cd CommonsConferenceUploader
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

4. **Run System Updater**
   ```bash
   php update.php
   ```

5. **Setup Cron** (optional, for scheduling)
   ```bash
   crontab -e
   # Add: */5 * * * * cd /path/to/app && php process-queue.php
   ```

6. **Start Development Server**
   ```bash
   php artisan serve
   ```

---

## Testing

### Manual Testing Checklist

**Conferences**:
- [ ] Create a new conference (conference_admin)
- [ ] View conference list
- [ ] View conference details with events
- [ ] Update conference details
- [ ] Delete conference (super_admin)
- [ ] Check conference statistics

**System Updater**:
- [ ] Run `php update.php --dry-run`
- [ ] Run `php update.php`
- [ ] Verify all tables created
- [ ] Check `system_updates` table for migration records

**Scheduling**:
- [ ] Schedule an immediate upload
- [ ] Schedule a future upload
- [ ] View scheduled uploads list
- [ ] Cancel a pending upload
- [ ] Run `php process-queue.php`
- [ ] Verify upload processed
- [ ] Check `publish_logs` for entries

---

## Breaking Changes

### None! 

All changes are **backward compatible**:
- Existing data structures preserved
- New columns added with safe defaults
- Foreign keys use SET NULL
- Existing API endpoints unchanged
- All existing features continue to work

---

## Support & Issues

For questions or issues related to these updates:
1. Check documentation in `/docs`
2. Review this UPDATE-NOTES.md file
3. Open an issue on GitHub
4. Contact project maintainer

---

## License

CommonsEventUploader
Copyright (C) 2025 Daniel Yepez Garces

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

See LICENSE file for full text.

---

**Last Updated**: 2025-01-18
**Version**: 1.0.0
**Implemented By**: GitHub Copilot + Daniel Yepez Garces
