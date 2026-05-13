# Course API Testing Guide

Base URL: `http://localhost:8000/api`  
Auth: All endpoints require `Authorization: Bearer {token}` header (except login).

---

## 0. Authentication — Get Your Token First

### Login as Admin
**POST** `/login`

```json
{
  "email": "admin@newproject.test",
  "password": "password"
}
```

**Response** — copy the `token` value and use it in all subsequent admin requests:
```
Authorization: Bearer {token}
```

---

## ADMIN COURSE ENDPOINTS

---

### 1. List All Courses
**GET** `/admin/courses/getAll`

> No request body needed. Returns paginated courses with summary cards.

**Optional query parameters:**

| Parameter | Values | Description |
|---|---|---|
| `status` | `draft` / `published` / `archived` | Filter by course status |
| `privacy` | `public` / `private` | Filter by visibility |
| `search` | any string | Search by course name |

Example: `GET /admin/courses/getAll?status=published&privacy=public`

---

### 2. Create a Course (with Availabilities)
**POST** `/admin/courses/create`

> Send as `multipart/form-data` if attaching an image, otherwise `application/json`.

```json
{
  "name": "Laravel From Scratch",
  "description": "A comprehensive course covering Laravel fundamentals and best practices.",
  "status": "draft",
  "privacy": "private",
  "level": "beginner",
  "duration": 40,
  "availabilities": [
    {
      "start_date": "2026-06-01",
      "end_date": "2026-08-31",
      "capacity": 20,
      "sessions": 24,
      "notes": "Morning batch — beginners welcome.",
      "days_of_week": ["monday", "wednesday", "friday"],
      "duration_weeks": 13,
      "session_time_shift_1": "09:00",
      "session_time_shift_2": "11:00",
      "session_duration_minutes": 90
    }
  ]
}
```

> Note the `id` from the response — you'll need it as `{courseId}` in all routes below.  
> Note the `availabilities[0].id` — you'll need it as `{availabilityId}`.

---

### 3. Create a Course with Multiple Availabilities
**POST** `/admin/courses/create`

```json
{
  "name": "Advanced PHP Patterns",
  "description": "Design patterns, SOLID principles, and performance tuning in PHP.",
  "status": "published",
  "privacy": "public",
  "level": "advanced",
  "duration": 60,
  "availabilities": [
    {
      "start_date": "2026-07-01",
      "end_date": "2026-09-30",
      "capacity": 15,
      "sessions": 30,
      "notes": "Shift 1 — early morning.",
      "days_of_week": ["tuesday", "thursday"],
      "duration_weeks": 13,
      "session_time_shift_1": "07:00",
      "session_duration_minutes": 120
    },
    {
      "start_date": "2026-07-01",
      "end_date": "2026-09-30",
      "capacity": 15,
      "sessions": 30,
      "notes": "Shift 2 — evening.",
      "days_of_week": ["tuesday", "thursday"],
      "duration_weeks": 13,
      "session_time_shift_2": "19:00",
      "session_duration_minutes": 120
    }
  ]
}
```

---

### 4. Create a Minimal Course (no optional fields)
**POST** `/admin/courses/create`

```json
{
  "name": "Quick SQL Refresher",
  "status": "published",
  "privacy": "public",
  "availabilities": [
    {
      "start_date": "2026-06-15",
      "end_date": "2026-07-15",
      "capacity": 30
    }
  ]
}
```

---

### 5. Get Course by ID
**GET** `/admin/courses/getById/{id}`

> Replace `{id}` with the course ID from step 2. No request body needed.

---

### 6. Update a Course
**PUT** `/admin/courses/update/{id}`

> Send as `multipart/form-data` if updating the image, otherwise `application/json`.

```json
{
  "name": "Laravel From Scratch — Updated",
  "status": "published",
  "privacy": "public",
  "level": "intermediate",
  "description": "Updated description with new modules added.",
  "duration": 50
}
```

---

### 7. Update a Course and Replace Its Availabilities
**PUT** `/admin/courses/update/{id}`

> Include the existing `id` in each availability to update it in-place.  
> Omit `id` to add a brand-new availability.

```json
{
  "availabilities": [
    {
      "id": 1,
      "start_date": "2026-06-01",
      "end_date": "2026-09-30",
      "capacity": 25,
      "sessions": 36,
      "notes": "Extended end date and increased capacity.",
      "days_of_week": ["monday", "wednesday", "friday"],
      "duration_weeks": 18,
      "session_time_shift_1": "09:00",
      "session_duration_minutes": 90
    }
  ]
}
```

---

### 8. Delete a Course (Soft Delete)
**DELETE** `/admin/courses/delete/{id}`

> No request body needed.

---

## ADMIN COURSE ASSIGNMENTS

---

### 9. List All Course Assignments
**GET** `/admin/course-assignments/getAll`

> No request body needed. Returns paginated assignments with summary cards.

**Optional query parameters:**

| Parameter | Description |
|---|---|
| `course_id` | Filter by a specific course |
| `user_id` | Filter by a specific user |

Example: `GET /admin/course-assignments/getAll?course_id=1`

---

### 10. Assign a Course to a User (without specific availability)
**POST** `/admin/course-assignments/create`

```json
{
  "course_id": 1,
  "user_id": 2
}
```

---

### 11. Assign a Course to a User (with specific availability)
**POST** `/admin/course-assignments/create`

```json
{
  "course_id": 1,
  "user_id": 3,
  "course_availability_id": 1
}
```

> Note the `id` from the response — save it as `{assignmentId}`.

---

### 12. Delete a Course Assignment
**DELETE** `/admin/course-assignments/delete/{id}`

> Replace `{id}` with the assignment ID. No request body needed.

---

## USER COURSE ENDPOINTS

> These require a **user-role token** (not admin). Login as a regular user first.

### Login as User
**POST** `/login`

```json
{
  "email": "jane@example.com",
  "password": "password"
}
```

---

### 13. Get All Accessible Courses
**GET** `/user/courses/getAll`

> No request body needed. Returns only courses the authenticated user can access (public courses + assigned private ones).

**Optional query parameter:**

| Parameter | Description |
|---|---|
| `search` | Filter by course name |

---

### 14. Get a Single Course by ID
**GET** `/user/courses/getById/{id}`

> No request body needed. Access-controlled — returns 403 if the user has no access to a private course.

---

### 15. Enroll in a Course
**POST** `/user/courses/enroll/{courseId}`

```json
{
  "course_availability_id": 1
}
```

> Saves a new course registration for the authenticated user.  
> Note the `id` from the response — save it as `{registrationId}`.

---

### 16. Mark a Course as Completed
**POST** `/user/courses/complete/{courseId}`

> No request body needed. Marks the authenticated user's active registration as completed.

---

### 17. Submit a Course Rating
**POST** `/user/courses/submitRating/{courseId}`

```json
{
  "rating": 5,
  "feedback": "Excellent course, very well structured and clearly explained."
}
```

---

### 17b. Submit a Minimal Rating (no feedback)
**POST** `/user/courses/submitRating/{courseId}`

```json
{
  "rating": 4
}
```

---

### 18. Get My Enrollments
**GET** `/user/courses/my-enrollments`

> No request body needed. Returns all course registrations for the authenticated user.

---

## TESTING FLOW — Step-by-Step Checklist

Follow this order for a complete end-to-end test:

```
Admin Flow:
[ ] 1.  POST /login  (admin credentials) → save admin token
[ ] 2.  POST /admin/courses/create  → save courseId, save availabilityId
[ ] 3.  GET  /admin/courses/getAll  → verify course appears
[ ] 4.  GET  /admin/courses/getAll?status=draft  → verify filter works
[ ] 5.  GET  /admin/courses/getById/{courseId}  → verify details & availabilities
[ ] 6.  PUT  /admin/courses/update/{courseId}  (set status: published, privacy: public)
[ ] 7.  GET  /admin/courses/getById/{courseId}  → confirm status change

Assignment Flow (Admin):
[ ] 8.  POST /admin/course-assignments/create  (course_id, user_id, course_availability_id) → save assignmentId
[ ] 9.  GET  /admin/course-assignments/getAll  → verify assignment appears
[ ] 10. GET  /admin/course-assignments/getAll?course_id={courseId}  → verify filter

User Flow:
[ ] 11. POST /login  (user credentials) → save user token
[ ] 12. GET  /user/courses/getAll  → course should appear (public or assigned)
[ ] 13. GET  /user/courses/getById/{courseId}  → verify access
[ ] 14. POST /user/courses/enroll/{courseId}  (course_availability_id) → save registrationId
[ ] 15. GET  /user/courses/my-enrollments  → confirm enrollment
[ ] 16. POST /user/courses/complete/{courseId}  → mark completed
[ ] 17. POST /user/courses/submitRating/{courseId}  (rating + feedback)
[ ] 18. GET  /user/courses/my-enrollments  → verify status = completed & rating saved

Cleanup (Admin):
[ ] 19. DELETE /admin/course-assignments/delete/{assignmentId}
[ ] 20. DELETE /admin/courses/delete/{courseId}
```

---

## Common Headers

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_token_here}
```

> When uploading an image, use `Content-Type: multipart/form-data` and send `availabilities` as a JSON-encoded string in the form field.

---

## Field Reference

### Course Fields

| Field | Required | Type | Values / Notes |
|---|---|---|---|
| `name` | Yes | string | max 255 characters |
| `description` | No | string | — |
| `image` | No | file | image, max 4 MB |
| `status` | Yes | string | `draft`, `published`, `archived` |
| `privacy` | Yes | string | `public`, `private` |
| `level` | No | string | `beginner`, `intermediate`, `advanced` |
| `duration` | No | number | total hours (≥ 0) |

### Availability Fields

| Field | Required | Type | Notes |
|---|---|---|---|
| `start_date` | Yes | date | `YYYY-MM-DD` |
| `end_date` | Yes | date | must be after `start_date` |
| `capacity` | Yes | integer | min 1 |
| `sessions` | No | integer | total session count (≥ 0) |
| `notes` | No | string | free text |
| `days_of_week` | No | array | see days reference below |
| `duration_weeks` | No | integer | min 1 |
| `session_time_shift_1` | No | string | `HH:mm` format |
| `session_time_shift_2` | No | string | `HH:mm` format |
| `session_time_shift_3` | No | string | `HH:mm` format |
| `session_duration_minutes` | No | integer | min 1 |

### Rating Fields

| Field | Required | Type | Notes |
|---|---|---|---|
| `rating` | Yes | integer | 1 – 5 |
| `feedback` | No | string | max 1000 characters |

---

## Enum Values Reference

### status

| Value | Meaning |
|---|---|
| `draft` | Not visible to users |
| `published` | Active, users can enroll |
| `archived` | Hidden / disabled |

### privacy

| Value | Meaning |
|---|---|
| `public` | Visible to all authenticated users |
| `private` | Visible only to assigned users |

### level

| Value | Meaning |
|---|---|
| `beginner` | Entry-level course |
| `intermediate` | Requires prior knowledge |
| `advanced` | Expert-level content |

### days_of_week

| Value |
|---|
| `monday` |
| `tuesday` |
| `wednesday` |
| `thursday` |
| `friday` |
| `saturday` |
| `sunday` |
