# Blog / Podcast API Testing Guide

Base URL: `http://localhost:8000/api`  
Auth: Admin endpoints require `Authorization: Bearer {token}` header. User interaction endpoints also require a user token. Public feed endpoints work without any token.

---

## 0. Authentication — Get Your Tokens First

### Login as Admin
**POST** `/login`

```json
{
  "email": "admin@newproject.test",
  "password": "password"
}
```

**Response** — copy the `token` value and use it in all admin requests:
```
Authorization: Bearer {admin_token}
```

### Login as Regular User
**POST** `/login`

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response** — copy the `token` value and use it in user interaction requests:
```
Authorization: Bearer {user_token}
```

---

## ADMIN BLOG POST ENDPOINTS

> All endpoints below require `Authorization: Bearer {admin_token}`.

---

### 1. List All Blog Posts (Admin)
**GET** `/admin/blog-posts`

> No request body needed. Returns paginated posts with full admin detail.

**Optional query parameters:**

| Parameter | Description |
|---|---|
| `per_page` | Number of results per page (default: 15) |

Example: `GET /admin/blog-posts?per_page=10`

---

### 2. Create a Text-Only Blog Post (Draft)
**POST** `/admin/blog-posts`

> Send as `application/json`. Use `multipart/form-data` only when attaching a thumbnail image.

```json
{
  "title": "Getting Started with Laravel 11",
  "excerpt": "A quick overview of what changed in Laravel 11 and how to upgrade.",
  "description": "Laravel 11 brought a streamlined application structure, a new minimal skeleton, and several quality-of-life improvements...",
  "status": "draft",
  "tags": ["laravel", "php", "backend"]
}
```

> Note the `id` from the response — you'll need it as `{postId}` in all routes below.  
> Note the `slug` from the response — you'll need it as `{slug}` in public feed routes.

---

### 3. Create a Blog Post and Publish It Immediately
**POST** `/admin/blog-posts`

```json
{
  "title": "Understanding API Authentication",
  "slug": "understanding-api-authentication",
  "excerpt": "Tokens, sessions, and signed URLs explained.",
  "description": "Modern APIs rely on stateless authentication. This post covers Laravel Sanctum tokens and signed URL patterns...",
  "status": "published",
  "tags": ["api", "security", "sanctum"]
}
```

---

### 4. Create a Blog Post Attached to a Video
**POST** `/admin/blog-posts`

> First call `GET /admin/blog-posts/available-videos` (step 12) to find a valid `mediable_id`.

```json
{
  "title": "Video: Clean Architecture in PHP",
  "excerpt": "Watch and learn how to structure a Laravel project using clean architecture principles.",
  "description": "In this video post we walk through a full refactor of a monolithic controller into layered services and repositories...",
  "status": "published",
  "tags": ["video", "architecture", "php"],
  "mediable_type": "App\\Models\\Video",
  "mediable_id": 1
}
```

---

### 5. Create a Blog Post Attached to an Audio / Podcast
**POST** `/admin/blog-posts`

> First call `GET /admin/blog-posts/available-audios` (step 13) to find a valid `mediable_id`.

```json
{
  "title": "Podcast: The Future of Backend Development",
  "excerpt": "Our team discusses serverless, edge computing, and where backend is heading in 2027.",
  "description": "Join us for a 45-minute deep dive into trends shaping backend engineering...",
  "status": "published",
  "tags": ["podcast", "backend", "trends"],
  "mediable_type": "App\\Models\\Audio",
  "mediable_id": 1
}
```

---

### 6. Create a Minimal Blog Post (required fields only)
**POST** `/admin/blog-posts`

```json
{
  "title": "Quick Note on PHP 8.4"
}
```

---

### 7. Get Blog Post by ID (Admin)
**GET** `/admin/blog-posts/{id}`

> Replace `{id}` with the post ID from step 2. No request body needed.

---

### 8. Update a Blog Post Title and Status
**PUT** `/admin/blog-posts/{id}`

> Send as `application/json`. Only send the fields you want to change.

```json
{
  "title": "Getting Started with Laravel 11 — Updated",
  "status": "published"
}
```

---

### 9. Update a Blog Post and Swap Its Media
**PUT** `/admin/blog-posts/{id}`

```json
{
  "mediable_type": "App\\Models\\Audio",
  "mediable_id": 2
}
```

---

### 10. Update a Blog Post — Remove Its Media Attachment
**PUT** `/admin/blog-posts/{id}`

```json
{
  "mediable_type": null,
  "mediable_id": null
}
```

---

### 11. Delete a Blog Post
**DELETE** `/admin/blog-posts/{id}`

> No request body needed. Cascades to all comments and likes for this post.

---

### 12. Get Available Videos (for media attachment)
**GET** `/admin/blog-posts/available-videos`

> No request body needed. Returns all videos with a completed transcode status.  
> Use the `id` from this list as `mediable_id` when attaching a video to a post.

---

### 13. Get Available Audios (for media attachment)
**GET** `/admin/blog-posts/available-audios`

> No request body needed. Returns all audios that have a processed file on disk.  
> Use the `id` from this list as `mediable_id` when attaching an audio to a post.

---

## PUBLIC BLOG FEED ENDPOINTS

> These endpoints require **no authentication**. They return only published posts.

---

### 14. Get Published Blog Feed
**GET** `/blog-posts`

> Returns a paginated list of published posts ordered by `published_at` descending.

**Optional query parameters:**

| Parameter | Description |
|---|---|
| `per_page` | Number of results per page (default: 15) |

Example: `GET /blog-posts?per_page=5`

---

### 15. Get a Single Post by Slug (Public)
**GET** `/blog-posts/{slug}`

> Replace `{slug}` with the post slug from the feed (e.g. `getting-started-with-laravel-11`).  
> If the post has attached media, the response includes a `stream_url` (a time-limited signed URL valid for 4 hours).  
> If you send the request with a valid `Bearer` token, the response also includes `is_liked` (boolean).

Example: `GET /blog-posts/understanding-api-authentication`

---

## USER INTERACTION ENDPOINTS

> All endpoints below require `Authorization: Bearer {user_token}`.

---

### 16. Like a Post
**POST** `/blog-posts/{id}/like`

> Replace `{id}` with the numeric post ID (not slug). No request body needed.  
> Calling this on an already-liked post is idempotent — it will not create a duplicate.

**Response:**
```json
{
  "like_count": 1,
  "is_liked": true
}
```

---

### 17. Unlike a Post
**DELETE** `/blog-posts/{id}/like`

> No request body needed.

**Response:**
```json
{
  "like_count": 0,
  "is_liked": false
}
```

---

### 18. Post a Comment
**POST** `/blog-posts/{id}/comments`

```json
{
  "body": "Really enjoyed this post — the section on signed URLs was especially clear."
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "body": "Really enjoyed this post — the section on signed URLs was especially clear.",
    "created_at": "2026-05-25T10:00:00.000000Z",
    "author": {
      "id": 2,
      "name": "Jane Doe"
    }
  }
}
```

---

### 19. Delete a Comment
**DELETE** `/blog-comments/{id}`

> Replace `{id}` with the comment ID from step 18.  
> Only the comment owner or an admin can delete a comment. Returns 403 otherwise.

---

## MEDIA STREAMING

> Stream URLs are returned automatically inside the `show` response (step 15).  
> You do not call these endpoints directly — use the `stream_url` from the post detail response.  
> The URL is pre-signed and expires after **4 hours**.

### How it works

1. Call `GET /blog-posts/{slug}` — the response includes:
   ```json
   {
     "data": {
       "media": {
         "type": "video",
         "stream_url": "http://localhost:8000/api/media/blog-video/1?expires=...&signature=..."
       }
     }
   }
   ```
2. Pass `stream_url` directly to an HTML5 `<video>` or `<audio>` src attribute.
3. The player streams the file in chunks using HTTP Range requests (seek support included).

### Direct stream endpoints (for reference)

| Endpoint | Description |
|---|---|
| `GET /media/blog-video/{video_id}?expires=...&signature=...` | Stream a blog video (signed URL required) |
| `GET /media/blog-audio/{audio_id}?expires=...&signature=...` | Stream a blog audio (signed URL required) |

> Calling these endpoints without a valid signature returns **403 Forbidden**.

---

## TESTING FLOW — Step-by-Step Checklist

Follow this order for a complete end-to-end test:

```
Admin Setup:
[ ] 1.  POST /login  (admin credentials) → save admin_token
[ ] 2.  GET  /admin/blog-posts/available-videos → note a video id
[ ] 3.  GET  /admin/blog-posts/available-audios → note an audio id

Admin Post Management:
[ ] 4.  POST /admin/blog-posts  (text-only, status: draft) → save postId, save slug
[ ] 5.  GET  /admin/blog-posts  → verify post appears in list
[ ] 6.  GET  /admin/blog-posts/{postId} → verify all fields
[ ] 7.  PUT  /admin/blog-posts/{postId}  (set status: published, attach video or audio)
[ ] 8.  GET  /admin/blog-posts/{postId} → confirm status = published, mediable_id set

Public Feed:
[ ] 9.  GET  /blog-posts → post should appear (no token needed)
[ ] 10. GET  /blog-posts/{slug} → verify detail, check media.stream_url is present
[ ] 11. Copy stream_url → open in browser or media player to verify streaming works

User Interactions:
[ ] 12. POST /login  (user credentials) → save user_token
[ ] 13. GET  /blog-posts/{slug}  (with user_token) → verify is_liked = false
[ ] 14. POST /blog-posts/{postId}/like → verify like_count = 1, is_liked = true
[ ] 15. GET  /blog-posts/{slug}  (with user_token) → verify is_liked = true
[ ] 16. DELETE /blog-posts/{postId}/like → verify like_count = 0, is_liked = false
[ ] 17. POST /blog-posts/{postId}/comments  (body: "Great post!") → save commentId
[ ] 18. GET  /blog-posts/{slug} → verify comment appears in comments array
[ ] 19. DELETE /blog-comments/{commentId} → verify 200 response

Cleanup (Admin):
[ ] 20. DELETE /admin/blog-posts/{postId} → verify 200 response
[ ] 21. GET  /blog-posts/{slug} → should return 404
```

---

## Common Headers

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_token_here}
```

> When uploading a thumbnail image, use `Content-Type: multipart/form-data` and send all other fields as regular form fields.

---

## Field Reference

### Blog Post Fields

| Field | Required | Type | Values / Notes |
|---|---|---|---|
| `title` | Yes (create) | string | max 255 characters |
| `slug` | No | string | auto-generated from title if omitted; lowercase letters, numbers, and hyphens only |
| `excerpt` | No | string | max 500 characters; short summary shown in feed cards |
| `description` | No | string | full rich-text body of the post |
| `status` | No | string | `draft` (default) or `published` |
| `tags` | No | array of strings | each tag max 50 characters |
| `mediable_type` | No* | string | `App\Models\Video` or `App\Models\Audio`; required if `mediable_id` is set |
| `mediable_id` | No* | integer | ID from available-videos or available-audios list; required if `mediable_type` is set |
| `thumbnail` | No | file | image file, max 4 MB |

### Comment Fields

| Field | Required | Type | Notes |
|---|---|---|---|
| `body` | Yes | string | 1 – 2000 characters |

---

## Enum Values Reference

### status

| Value | Meaning |
|---|---|
| `draft` | Not visible in public feed |
| `published` | Visible in public feed; `published_at` is set automatically on first publish |

### mediable_type

| Value | Meaning |
|---|---|
| `App\Models\Video` | Post is attached to a transcoded video |
| `App\Models\Audio` | Post is attached to a processed audio / podcast |

---

## Response Shape Reference

### Feed card (`GET /blog-posts`)
```json
{
  "data": [
    {
      "id": 1,
      "title": "Understanding API Authentication",
      "slug": "understanding-api-authentication",
      "excerpt": "Tokens, sessions, and signed URLs explained.",
      "thumbnail_url": null,
      "status": "published",
      "published_at": "2026-05-25T10:00:00.000000Z",
      "tags": ["api", "security"],
      "has_media": true,
      "media_type": "Video",
      "like_count": 3,
      "comment_count": 1,
      "author": { "id": 1, "name": "Admin" },
      "created_at": "2026-05-25T09:00:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### Post detail (`GET /blog-posts/{slug}`)
```json
{
  "data": {
    "id": 1,
    "title": "Understanding API Authentication",
    "slug": "understanding-api-authentication",
    "excerpt": "Tokens, sessions, and signed URLs explained.",
    "description": "Modern APIs rely on stateless authentication...",
    "thumbnail_url": null,
    "status": "published",
    "published_at": "2026-05-25T10:00:00.000000Z",
    "tags": ["api", "security"],
    "media": {
      "type": "video",
      "id": 1,
      "stream_url": "http://localhost:8000/api/media/blog-video/1?expires=...&signature=..."
    },
    "is_liked": false,
    "like_count": 0,
    "comments": [],
    "created_at": "2026-05-25T09:00:00.000000Z",
    "updated_at": "2026-05-25T10:00:00.000000Z"
  }
}
```

### Like response (`POST /blog-posts/{id}/like`)
```json
{
  "like_count": 1,
  "is_liked": true
}
```
