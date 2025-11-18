# CommonsEventUploader API Documentation

All API endpoints return JSON responses and require appropriate authentication where indicated.

## Authentication

### Login
Initiates OAuth 2.0 flow with Wikimedia.

```
GET /auth/login
```

Redirects to Wikimedia OAuth authorization page.

### Callback
OAuth callback handler. Automatically called by Wikimedia after authorization.

```
GET /auth/callback?code={code}&state={state}
```

### Current User
Get information about the currently authenticated user.

```
GET /auth/me
```

**Response:**
```json
{
  "id": 1,
  "username": "WikiUser",
  "role": "user",
  "avatar": "https://..."
}
```

### Logout
Logout the current user.

```
POST /auth/logout
```

## Events

### List Events
Get all events (super_admin) or user's events.

```
GET /events?limit=100&offset=0
```

**Response:**
```json
[
  {
    "id": 1,
    "title": "WikiConference 2025",
    "slug": "wikiconference-2025",
    "description": "Annual conference",
    "start_date": "2025-01-15",
    "end_date": "2025-01-17",
    "wikidata_event_id": "Q12345",
    "created_by": 1,
    "created_at": "2025-01-01 10:00:00"
  }
]
```

### Get Event
Get details of a specific event.

```
GET /events/{id}
```

### Create Event
Create a new event (conference_admin or super_admin only).

```
POST /events
Content-Type: application/json
X-CSRF-Token: {token}

{
  "title": "New Conference",
  "description": "Conference description",
  "start_date": "2025-06-01",
  "end_date": "2025-06-03",
  "wikidata_event_id": "Q67890"
}
```

### Update Event
Update an event (owner or super_admin only).

```
PUT /events/{id}
Content-Type: application/json
X-CSRF-Token: {token}

{
  "title": "Updated Title",
  "description": "Updated description"
}
```

### Delete Event
Delete an event (owner or super_admin only).

```
DELETE /events/{id}
X-CSRF-Token: {token}
```

### Event Statistics
Get upload statistics for an event.

```
GET /events/{id}/stats
```

**Response:**
```json
{
  "total_uploads": 25,
  "successful_uploads": 23,
  "unique_contributors": 8
}
```

## Uploads

### Upload File
Upload a file to Commons for a specific event.

```
POST /events/{id}/upload
Content-Type: multipart/form-data
X-CSRF-Token: {token}

file: [binary file data]
title: "My Photo"
description: "Photo from the conference"
categories: [1, 2, 3]
main_category_id: 1
```

**Response:**
```json
{
  "success": true,
  "upload": {
    "id": 10,
    "event_id": 1,
    "user_id": 5,
    "title": "My Photo",
    "commons_filename": "wikiconference-2025_My_Photo_1234567890.jpg",
    "status": "uploaded"
  },
  "commons_url": "https://commons.wikimedia.org/wiki/File:wikiconference-2025_My_Photo_1234567890.jpg"
}
```

### List Event Uploads
Get all uploads for an event.

```
GET /events/{id}/uploads?limit=100&offset=0
```

### My Uploads
Get current user's uploads.

```
GET /uploads/my?limit=100&offset=0
```

## Categories

### List Categories
Get all categories.

```
GET /categories?limit=100&offset=0
```

### Search Categories
Search for categories by name.

```
GET /categories/search?q={query}&limit=20
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "WikiConferences",
    "description": "Photos from WikiConferences",
    "total_uploads": 150
  }
]
```

### Top Categories
Get top categories by upload count.

```
GET /categories/top?limit=10
```

### Get Category
Get details of a specific category.

```
GET /categories/{id}
```

### Create Category
Create a new category (super_admin only).

```
POST /categories
Content-Type: application/json
X-CSRF-Token: {token}

{
  "name": "New Category",
  "description": "Category description"
}
```

### Update Category
Update a category (super_admin only).

```
PUT /categories/{id}
Content-Type: application/json
X-CSRF-Token: {token}

{
  "name": "Updated Name",
  "description": "Updated description"
}
```

### Delete Category
Delete a category (super_admin only).

```
DELETE /categories/{id}
X-CSRF-Token: {token}
```

## Statistics

### User Statistics
Get statistics for the current user.

```
GET /stats/user
```

**Response:**
```json
{
  "total_uploads": 12,
  "successful_uploads": 10,
  "events_participated": 3
}
```

### Global Statistics
Get global statistics (super_admin only).

```
GET /stats/global
```

**Response:**
```json
{
  "overall": {
    "total_uploads": 500,
    "successful_uploads": 475,
    "total_users": 50,
    "total_events": 10
  },
  "top_categories": [
    {
      "id": 1,
      "name": "WikiConferences",
      "total_uploads": 150
    }
  ]
}
```

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
  "error": "Missing required fields"
}
```

### 401 Unauthorized
```json
{
  "error": "Unauthorized"
}
```

### 403 Forbidden
```json
{
  "error": "Forbidden: Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "error": "Resource not found"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error"
}
```

## CSRF Protection

All state-changing requests (POST, PUT, DELETE) require a CSRF token. Include it as:
- Header: `X-CSRF-Token: {token}`
- Form field: `csrf_token={token}`

Get the token from the session or by calling a dedicated endpoint (to be implemented).

## Rate Limiting

No rate limiting is currently implemented. Consider implementing rate limiting for production use.

## File Upload Limits

- Maximum file size: 100MB
- Allowed file types: jpg, jpeg, png, gif, svg, webp, tiff, pdf
