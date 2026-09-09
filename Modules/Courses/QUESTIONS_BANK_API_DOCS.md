# Questions Bank API Documentation

## Overview

This API provides CRUD operations for managing questions bank with multiple choice options.

**Base URL:** `/api/admin/questions-bank`

**Authentication:** All endpoints require Admin authentication via `AdminAuthentication` middleware.

---

## Database Structure

### questions_bank Table

-   `id` - Primary key
-   `question_text` - The question text
-   `question_type` - Type of question (mcq, true_false, short_answer)
-   `created_at` - Timestamp
-   `updated_at` - Timestamp

### question_bank_options Table

-   `id` - Primary key
-   `question_id` - Foreign key to questions_bank
-   `option_text` - The option text
-   `is_correct` - Boolean (0 or 1)
-   `created_at` - Timestamp
-   `updated_at` - Timestamp

---

## Endpoints

### 1. Get All Questions

Retrieve a paginated list of all questions with their options.

**Method:** `GET`  
**Endpoint:** `/api/admin/questions-bank/getAllQuestions`

**Query Parameters:**

-   `per_page` (optional, integer, default: 10) - Number of items per page
-   `question_type` (optional, string) - Filter by type: mcq, true_false, short_answer

**Request Example:**

```
GET /api/admin/questions-bank/getAllQuestions?per_page=15&question_type=mcq
```

**Success Response (200):**

```json
{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "question_text": "What is Laravel?",
                "question_type": "mcq",
                "created_at": "2026-01-15T12:00:00.000000Z",
                "updated_at": "2026-01-15T12:00:00.000000Z",
                "options": [
                    {
                        "id": 1,
                        "question_id": 1,
                        "option_text": "A PHP Framework",
                        "is_correct": true,
                        "created_at": "2026-01-15T12:00:00.000000Z",
                        "updated_at": "2026-01-15T12:00:00.000000Z"
                    },
                    {
                        "id": 2,
                        "question_id": 1,
                        "option_text": "A JavaScript Framework",
                        "is_correct": false,
                        "created_at": "2026-01-15T12:00:00.000000Z",
                        "updated_at": "2026-01-15T12:00:00.000000Z"
                    }
                ]
            }
        ],
        ...pagination data
    }
}
```

---

### 2. Get Questions By Type

Retrieve questions filtered by specific type.

**Method:** `POST`  
**Endpoint:** `/api/admin/questions-bank/getQuestionsByType`

**Request Body:**

```json
{
    "question_type": "mcq",
    "per_page": 10
}
```

**Validation Rules:**

-   `question_type` (required, string, must be: mcq, true_false, or short_answer)
-   `per_page` (optional, integer, default: 10)

**Success Response (200):**

```json
{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [...questions with options],
        ...pagination data
    }
}
```

---

### 3. Show Question by ID

Retrieve a specific question with its options.

**Method:** `POST`  
**Endpoint:** `/api/admin/questions-bank/showQuestion`

**Request Body:**

```json
{
    "id": 1
}
```

**Success Response (200):**

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "question_text": "What is Laravel?",
        "question_type": "mcq",
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T12:00:00.000000Z",
        "options": [
            {
                "id": 1,
                "question_id": 1,
                "option_text": "A PHP Framework",
                "is_correct": true,
                "created_at": "2026-01-15T12:00:00.000000Z",
                "updated_at": "2026-01-15T12:00:00.000000Z"
            }
        ]
    }
}
```

**Error Response (404):**

```json
{
    "status": "error",
    "message": "السؤال غير موجود"
}
```

---

### 4. Create New Question

Create a new question with its options.

**Method:** `POST`  
**Endpoint:** `/api/admin/questions-bank/storeQuestion`

**Request Body:**

```json
{
    "question_text": "What is Laravel?",
    "question_type": "mcq",
    "options": [
        {
            "option_text": "A PHP Framework",
            "is_correct": true
        },
        {
            "option_text": "A JavaScript Framework",
            "is_correct": false
        },
        {
            "option_text": "A Python Framework",
            "is_correct": false
        }
    ]
}
```

**Validation Rules:**

-   `question_text` (required, string)
-   `question_type` (required, string, must be: mcq, true_false, or short_answer)
-   `options` (required, array, minimum 2 options)
-   `options.*.option_text` (required, string)
-   `options.*.is_correct` (required, boolean)

**Success Response (201):**

```json
{
    "status": "success",
    "message": "تم إنشاء السؤال بنجاح",
    "data": {
        "id": 1,
        "question_text": "What is Laravel?",
        "question_type": "mcq",
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T12:00:00.000000Z",
        "options": [...]
    }
}
```

---

### 5. Update Question

Update an existing question and its options.

**Method:** `POST`  
**Endpoint:** `/api/admin/questions-bank/updateQuestion`

**Request Body:**

```json
{
    "id": 1,
    "question_text": "Updated question text",
    "question_type": "mcq",
    "options": [
        {
            "option_text": "Updated option 1",
            "is_correct": true
        },
        {
            "option_text": "Updated option 2",
            "is_correct": false
        }
    ]
}
```

**Validation Rules:**

-   `id` (required, must exist in questions_bank table)
-   `question_text` (optional, string)
-   `question_type` (optional, string, must be: mcq, true_false, or short_answer)
-   `options` (optional, array, minimum 2 if provided)
-   `options.*.option_text` (required if options provided, string)
-   `options.*.is_correct` (required if options provided, boolean)

**Note:** When updating options, all old options are deleted and replaced with new ones.

**Success Response (200):**

```json
{
    "status": "success",
    "message": "تم تحديث السؤال بنجاح",
    "data": {
        "id": 1,
        "question_text": "Updated question text",
        "question_type": "mcq",
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T15:00:00.000000Z",
        "options": [...]
    }
}
```

---

### 6. Delete Question

Delete a question and all its options.

**Method:** `POST`  
**Endpoint:** `/api/admin/questions-bank/deleteQuestion`

**Request Body:**

```json
{
    "id": 1
}
```

**Success Response (200):**

```json
{
    "status": "success",
    "message": "تم حذف السؤال بنجاح"
}
```

**Note:** This will automatically delete all associated options in the database.

---

## Error Responses

### 422 - Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "question_text": ["نص السؤال مطلوب"],
        "options": ["يجب إضافة خيارين على الأقل"]
    }
}
```

### 404 - Not Found

```json
{
    "status": "error",
    "message": "السؤال غير موجود"
}
```

### 500 - Server Error

```json
{
    "status": "error",
    "message": "فشل في إنشاء السؤال",
    "error": "Detailed error message"
}
```

---

## Question Types

The system supports three question types:

-   **mcq** - Multiple Choice Question
-   **true_false** - True/False Question
-   **short_answer** - Short Answer Question

---

## Important Notes

1. **Transaction Safety**: All create, update, and delete operations use database transactions for data integrity
2. **Cascade Delete**: Deleting a question automatically deletes all its options
3. **Options Update**: When updating options, ALL old options are deleted and replaced with the new set
4. **Minimum Options**: You must provide at least 2 options when creating or updating a question
5. **Arabic Messages**: All response messages are in Arabic (العربية)
6. **Pagination**: Default pagination is 10 items per page, maximum can be adjusted
7. **Relationships**: Questions automatically load their options in responses

---

## Example Usage (Postman)

### Create Question Example:

1. Method: `POST`
2. URL: `http://your-domain.com/api/admin/questions-bank/storeQuestion`
3. Headers:
    - `Authorization: Bearer YOUR_ADMIN_TOKEN`
    - `Content-Type: application/json`
    - `Accept: application/json`
4. Body (JSON):

```json
{
    "question_text": "ما هو Laravel؟",
    "question_type": "mcq",
    "options": [
        {
            "option_text": "إطار عمل PHP",
            "is_correct": true
        },
        {
            "option_text": "إطار عمل JavaScript",
            "is_correct": false
        },
        {
            "option_text": "إطار عمل Python",
            "is_correct": false
        }
    ]
}
```

### Update Question Example:

Same as create, but include the `id` field and only the fields you want to update.

---

## Database Relationships

-   **One-to-Many**: One question can have many options
-   **Foreign Key**: `question_bank_options.question_id` references `questions_bank.id`
-   **Cascade**: Options are automatically deleted when parent question is deleted
