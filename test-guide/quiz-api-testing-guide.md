# Quiz API Testing Guide

Base URL: `http://localhost:8000/api`  
Auth: All endpoints require `Authorization: Bearer {token}` header (except login).

---

## 0. Authentication — Get Your Token First

### Login
**POST** `/login`

```json
{
  "email": "admin@newproject.test",
  "password": "password"
}
```

**Response** — copy the `token` value and use it in all subsequent requests as:
```
Authorization: Bearer {token}
```

---

## ADMIN QUIZ ENDPOINTS

---

### 1. List All Quizzes
**GET** `/admin/quizzes/getAll`

> No request body needed.

#### Query Parameters

| Name | Type | Required | Description |
|---|---|---|---|
| `status` | string | No | Filter quizzes by status (`draft`, `published`, `archived`). |
| `course_id` | integer | No | Filter quizzes by course ID. |

#### Example Requests

- `GET /admin/quizzes/getAll`
- `GET /admin/quizzes/getAll?status=published`
- `GET /admin/quizzes/getAll?status=draft&course_id=12`

---

### 2. Create a Quiz (with questions inline)
**POST** `/admin/quizzes/create`

```json
{
  "title": "PHP Fundamentals Quiz",
  "description": "Test your knowledge of core PHP concepts.",
  "course_id": null,
  "course_online_id": null,
  "module_id": null,
  "required_to_proceed": true,
  "max_attempts": 3,
  "retry_delay_hours": 24,
  "show_correct_answers": "after_pass",
  "deadline": "2026-12-31T23:59:59Z",
  "time_limit_minutes": 30,
  "status": "draft",
  "pass_threshold": 80,
  "questions": [
    {
      "question_text": "What does PHP stand for?",
      "type": "radio",
      "points": 10,
      "options": [
        "Personal Home Page",
        "PHP: Hypertext Preprocessor",
        "Preprocessed HTML Page"
      ],
      "correct_answer": ["PHP: Hypertext Preprocessor"],
      "correct_answer_explanation": "PHP originally stood for 'Personal Home Page' but now officially stands for 'PHP: Hypertext Preprocessor'.",
      "order": 1
    },
    {
      "question_text": "Which of the following are valid PHP scalar types?",
      "type": "checkbox",
      "points": 15,
      "options": ["string", "integer", "float", "boolean", "collection"],
      "correct_answer": ["string", "integer", "float", "boolean"],
      "correct_answer_explanation": "PHP scalar types are: string, integer, float and boolean.",
      "order": 2
    },
    {
      "question_text": "Explain the difference between == and === in PHP.",
      "type": "text",
      "points": null,
      "order": 3
    }
  ]
}
```

> Note the `id` from the response — you'll need it as `{quizId}` in all routes below.

---

### 3. Create a Minimal Quiz (no questions, published)
**POST** `/admin/quizzes/create`

```json
{
  "title": "Quick JavaScript Quiz",
  "description": "A short JS knowledge check.",
  "status": "published",
  "pass_threshold": 70,
  "max_attempts": 5,
  "time_limit_minutes": 15,
  "show_correct_answers": "always",
  "required_to_proceed": false,
  "retry_delay_hours": 0
}
```

---

### 4. Get Quiz by ID
**GET** `/admin/quizzes/getById/{id}`

> Replace `{id}` with the quiz ID from step 2. No request body needed.

---

### 5. Update a Quiz
**PUT** `/admin/quizzes/update/{id}`

```json
{
  "title": "PHP Fundamentals Quiz – Revised",
  "status": "published",
  "max_attempts": 5,
  "pass_threshold": 70,
  "time_limit_minutes": 45,
  "show_correct_answers": "after_max_attempts",
  "deadline": "2027-06-30T23:59:59Z"
}
```

---

### 6. Delete a Quiz (Soft Delete)
**DELETE** `/admin/quizzes/delete/{id}`

> No request body needed.

---

## ADMIN QUIZ QUESTIONS

> Use `{quizId}` = the ID of a quiz created above.

---

### 7. Add a Radio Question to a Quiz
**POST** `/admin/quizzes/{quizId}/questions/create`

```json
{
  "question_text": "What is the default port for MySQL?",
  "type": "radio",
  "points": 10,
  "options": ["3306", "5432", "1433", "8080"],
  "correct_answer": ["3306"],
  "correct_answer_explanation": "MySQL runs on port 3306 by default.",
  "order": 1
}
```

---

### 8. Add a Checkbox Question to a Quiz
**POST** `/admin/quizzes/{quizId}/questions/create`

```json
{
  "question_text": "Which of the following are HTTP methods?",
  "type": "checkbox",
  "points": 15,
  "options": ["GET", "POST", "FETCH", "DELETE", "SEND"],
  "correct_answer": ["GET", "POST", "DELETE"],
  "correct_answer_explanation": "GET, POST, and DELETE are standard HTTP methods. FETCH and SEND are not.",
  "order": 2
}
```

---

### 9. Add a Text (Open-Ended) Question to a Quiz
**POST** `/admin/quizzes/{quizId}/questions/create`

```json
{
  "question_text": "Describe what RESTful API design means to you.",
  "type": "text",
  "points": null,
  "order": 3
}
```

> Note the `id` from each response — you'll need it as `{questionId}`.

---

### 10. Update a Question
**PUT** `/admin/quizzes/{quizId}/questions/update/{questionId}`

```json
{
  "question_text": "Which of the following are valid HTTP methods? (revised)",
  "points": 20,
  "order": 2,
  "options": ["GET", "POST", "FETCH", "DELETE", "PUT", "SEND"],
  "correct_answer": ["GET", "POST", "DELETE", "PUT"]
}
```

---

### 11. Delete a Question
**DELETE** `/admin/quizzes/{quizId}/questions/delete/{questionId}`

> No request body needed.

---

## ADMIN QUIZ ASSIGNMENTS

---

### 12. List All Quiz Assignments
**GET** `/admin/quiz-assignments/getAll`

> No request body needed.

#### Query Parameters

| Name | Type | Required | Description |
|---|---|---|---|
| `quiz_id` | integer | No | Filter assignments for a specific quiz. |
| `user_id` | integer | No | Filter assignments for a specific user. |
| `notification_sent` | boolean | No | Filter by notification state (`true` or `false`). |

#### Example Requests

- `GET /admin/quiz-assignments/getAll`
- `GET /admin/quiz-assignments/getAll?quiz_id=4`
- `GET /admin/quiz-assignments/getAll?user_id=9&notification_sent=false`

---

### 13. Assign a Quiz to One User
**POST** `/admin/quiz-assignments/create`

```json
{
  "quiz_id": 1,
  "user_ids": [2],
  "send_notification": true
}
```

---

### 14. Assign a Quiz to Multiple Users
**POST** `/admin/quiz-assignments/create`

```json
{
  "quiz_id": 1,
  "user_ids": [2, 3, 4],
  "send_notification": false
}
```

---

### 15. Delete a Quiz Assignment
**DELETE** `/admin/quiz-assignments/delete/{id}`

> Replace `{id}` with the assignment ID. No request body needed.

---

## ADMIN QUIZ ATTEMPTS

---

### 16. List All Attempts for a Quiz
**GET** `/admin/quizzes/{quizId}/attempts/getAll`

> No request body needed.
>
> Query Parameters: none

---

### 17. Get a Single Attempt with Answers
**GET** `/admin/quizzes/{quizId}/attempts/getById/{attemptId}`

> No request body needed.

---

## ADMIN MANUAL GRADING

---

### 18. Manually Grade a Text Answer
**POST** `/admin/quiz-answers/grade/{answerId}`

```json
{
  "points_earned": 8
}
```

> `answerId` = the ID of a specific answer from a quiz attempt (found in attempt detail response).  
> Use `0` to give no points, or any integer up to the question's point value.

---

### 18b. Give Full Points to a Text Answer
**POST** `/admin/quiz-answers/grade/{answerId}`

```json
{
  "points_earned": 15
}
```

---

## USER QUIZ ENDPOINTS

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

### 19. Get All Assigned Quizzes
**GET** `/user/quizzes/getAll`

> No request body needed. Returns only quizzes assigned to the authenticated user.
>
> Query Parameters: none

---

### 20. Get a Single Quiz (without correct answers)
**GET** `/user/quizzes/getById/{id}`

> No request body needed.

---

### 21. Start a Quiz Attempt
**POST** `/user/quizzes/{id}/start`

> No request body needed. Returns a new attempt with an `id` — save it as `{attemptId}`.

---

### 22. Submit Quiz Answers (radio + checkbox + text)
**POST** `/user/quizzes/{id}/submit/{attemptId}`

```json
{
  "answers": [
    {
      "quiz_question_id": 1,
      "answer": "PHP: Hypertext Preprocessor"
    },
    {
      "quiz_question_id": 2,
      "answer": "string"
    },
    {
      "quiz_question_id": 3,
      "answer": "The == operator compares values with type coercion, while === compares both value and type strictly, meaning the types must also match."
    }
  ]
}
```

> For **checkbox** questions, submit each selected option as a separate answer entry **or** as a comma-separated string — check what the API accepts.

---

### 22b. Submit Passing Answers (correct for radio + checkbox)
**POST** `/user/quizzes/{id}/submit/{attemptId}`

```json
{
  "answers": [
    {
      "quiz_question_id": 1,
      "answer": "PHP: Hypertext Preprocessor"
    },
    {
      "quiz_question_id": 2,
      "answer": "string,integer,float,boolean"
    },
    {
      "quiz_question_id": 3,
      "answer": "== checks value only with type coercion. === checks value AND type strictly."
    }
  ]
}
```

---

### 22c. Submit Failing Answers (all wrong)
**POST** `/user/quizzes/{id}/submit/{attemptId}`

```json
{
  "answers": [
    {
      "quiz_question_id": 1,
      "answer": "Personal Home Page"
    },
    {
      "quiz_question_id": 2,
      "answer": "collection"
    },
    {
      "quiz_question_id": 3,
      "answer": "They are the same thing."
    }
  ]
}
```

---

### 23. Get Quiz Result for a Completed Attempt
**GET** `/user/quizzes/{id}/result/{attemptId}`

> No request body needed.

---

## TESTING FLOW — Step-by-Step Checklist

Follow this order for a complete end-to-end test:

```
Admin Flow:
[ ] 1. POST /login  (admin credentials) → save token
[ ] 2. POST /admin/quizzes/create  → save quizId
[ ] 3. GET  /admin/quizzes/getAll  → verify quiz appears
[ ] 4. GET  /admin/quizzes/getById/{quizId}  → verify details
[ ] 5. POST /admin/quizzes/{quizId}/questions/create (radio)  → save questionId_1
[ ] 6. POST /admin/quizzes/{quizId}/questions/create (checkbox)  → save questionId_2
[ ] 7. POST /admin/quizzes/{quizId}/questions/create (text)  → save questionId_3
[ ] 8. PUT  /admin/quizzes/{quizId}/questions/update/{questionId_1}
[ ] 9. PUT  /admin/quizzes/update/{quizId}  (set status: published)
[ ] 10. POST /admin/quiz-assignments/create  (assign to user_id=2)  → save assignmentId
[ ] 11. GET  /admin/quiz-assignments/getAll  → verify assignment

User Flow:
[ ] 12. POST /login  (user credentials) → save user token
[ ] 13. GET  /user/quizzes/getAll  → quiz should appear
[ ] 14. GET  /user/quizzes/getById/{quizId}  → verify no correct_answer exposed
[ ] 15. POST /user/quizzes/{quizId}/start  → save attemptId
[ ] 16. POST /user/quizzes/{quizId}/submit/{attemptId}  (passing answers)
[ ] 17. GET  /user/quizzes/{quizId}/result/{attemptId}  → verify passed: true

Admin Review:
[ ] 18. GET  /admin/quizzes/{quizId}/attempts/getAll  → see attempt
[ ] 19. GET  /admin/quizzes/{quizId}/attempts/getById/{attemptId}  → see answers
[ ] 20. POST /admin/quiz-answers/grade/{textAnswerId}  (grade text question)
[ ] 21. DELETE /admin/quiz-assignments/delete/{assignmentId}
[ ] 22. DELETE /admin/quizzes/{quizId}/questions/delete/{questionId_3}
[ ] 23. DELETE /admin/quizzes/delete/{quizId}
```

---

## Common Headers

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_token_here}
```

---

## show_correct_answers Values Reference

| Value | Meaning |
|---|---|
| `never` | Never show correct answers |
| `after_pass` | Show only after user passes |
| `after_max_attempts` | Show after all attempts exhausted |
| `always` | Always show correct answers |

## status Values Reference

| Value | Meaning |
|---|---|
| `draft` | Not visible to users |
| `published` | Active, users can take it |
| `archived` | Hidden/disabled |
