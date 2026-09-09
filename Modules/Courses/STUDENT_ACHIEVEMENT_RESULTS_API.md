# Student Achievement Results API Documentation

## Overview

This API provides full CRUD operations for managing student achievement results. All endpoints require admin authentication.

**Base URL:** `/api/admin/student-achievement-results`

---

## Endpoints

### 1. Get All Student Achievement Results

**Endpoint:** `POST /getAllStudentAchievementResults`

**Description:** Retrieves a paginated list of all student achievement results with their category part information.

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body (Optional):**

```json
{
    "per_page": 10 // Optional, default is 10
}
```

**Success Response (200):**

```json
{
    "message": {
        "data": [
            {
                "id": 1,
                "category_part_id": 64,
                "title": "نتائج الطلاب المتميزين",
                "image": "student_achievement_results/1234567890_abc123.jpg",
                "video_link": "https://www.youtube.com/watch?v=example",
                "image_url": "http://127.0.0.1:8000/storage/student_achievement_results/1234567890_abc123.jpg",
                "category_part": {
                    "id": 64,
                    "name": "الرياضيات"
                }
            }
        ],
        "current_page": 1,
        "per_page": 10,
        "total": 50,
        "last_page": 5
    },
    "status": "success",
    "code": 200
}
```

---

### 2. Get Student Achievement Result by ID

**Endpoint:** `POST /getStudentAchievementResultById`

**Description:** Retrieves a specific student achievement result by its ID.

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "id": 1 // Required
}
```

**Success Response (200):**

```json
{
    "message": {
        "id": 1,
        "category_part_id": 64,
        "title": "نتائج الطلاب المتميزين",
        "image": "student_achievement_results/1234567890_abc123.jpg",
        "video_link": "https://www.youtube.com/watch?v=example",
        "image_url": "http://127.0.0.1:8000/storage/student_achievement_results/1234567890_abc123.jpg",
        "category_part": {
            "id": 64,
            "name": "الرياضيات"
        }
    },
    "status": "success",
    "code": 200
}
```

**Error Response (404):**

```json
{
    "message": "السجل غير موجود",
    "status": "error",
    "code": 404
}
```

---

### 3. Get Student Achievement Results by Category Part

**Endpoint:** `POST /getStudentAchievementResultsByCategoryPart`

**Description:** Retrieves all student achievement results for a specific category part.

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "category_part_id": 64 // Required
}
```

**Success Response (200):**

```json
{
    "message": [
        {
            "id": 1,
            "category_part_id": 64,
            "title": "نتائج الطلاب المتميزين",
            "image": "student_achievement_results/1234567890_abc123.jpg",
            "video_link": "https://www.youtube.com/watch?v=example",
            "image_url": "http://127.0.0.1:8000/storage/student_achievement_results/1234567890_abc123.jpg",
            "category_part": {
                "id": 64,
                "name": "الرياضيات"
            }
        }
    ],
    "status": "success",
    "code": 200
}
```

---

### 4. Add Student Achievement Result

**Endpoint:** `POST /addStudentAchievementResults`

**Description:** Creates a new student achievement result record with image upload.

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**

```
category_part_id: 64           // Required, must exist in category_parts table
title: "نتائج الطلاب المتميزين"  // Required, max 255 characters
image: [File]                  // Required, must be image (jpeg,png,jpg,gif,svg,webp), max 2MB
video_link: "https://..."      // Optional, max 500 characters
```

**Success Response (201):**

```json
{
    "message": {
        "id": 1,
        "category_part_id": 64,
        "title": "نتائج الطلاب المتميزين",
        "image": "student_achievement_results/1234567890_abc123.jpg",
        "video_link": "https://www.youtube.com/watch?v=example",
        "image_url": "http://127.0.0.1:8000/storage/student_achievement_results/1234567890_abc123.jpg"
    },
    "status": "تم إضافة السجل بنجاح",
    "code": 201
}
```

**Validation Error (422):**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "category_part_id": ["القسم المحدد غير موجود"],
        "title": ["العنوان مطلوب"],
        "image": ["الصورة مطلوبة"]
    }
}
```

---

### 5. Edit Student Achievement Result

**Endpoint:** `POST /editStudentAchievementResults`

**Description:** Updates an existing student achievement result. Can update image (old image will be deleted).

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**

```
id: 1                          // Required
category_part_id: 64           // Optional
title: "نتائج محدثة"           // Optional, max 255 characters
image: [File]                  // Optional, must be image (jpeg,png,jpg,gif,svg,webp), max 2MB
video_link: "https://..."      // Optional, max 500 characters
```

**Success Response (200):**

```json
{
    "message": {
        "id": 1,
        "category_part_id": 64,
        "title": "نتائج محدثة",
        "image": "student_achievement_results/1234567890_new123.jpg",
        "video_link": "https://www.youtube.com/watch?v=updated",
        "image_url": "http://127.0.0.1:8000/storage/student_achievement_results/1234567890_new123.jpg"
    },
    "status": "تم تعديل السجل بنجاح",
    "code": 200
}
```

**Error Response (404):**

```json
{
    "message": "السجل غير موجود",
    "status": "error",
    "code": 404
}
```

---

### 6. Delete Student Achievement Result

**Endpoint:** `POST /deleteStudentAchievementResults`

**Description:** Deletes a student achievement result record and its associated image file.

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "id": 1 // Required
}
```

**Success Response (200):**

```json
{
    "message": "تم حذف السجل بنجاح",
    "status": "success",
    "code": 200
}
```

**Error Response (404):**

```json
{
    "message": "السجل غير موجود",
    "status": "error",
    "code": 404
}
```

---

## Database Schema

**Table:** `student_achievement_results`

| Column           | Type         | Nullable | Description                   |
| ---------------- | ------------ | -------- | ----------------------------- |
| id               | INT          | NO       | Primary Key                   |
| category_part_id | INT          | NO       | Foreign Key to category_parts |
| title            | VARCHAR(255) | NO       | Title of the achievement      |
| image            | VARCHAR(255) | NO       | Relative path to image file   |
| video_link       | VARCHAR(500) | YES      | Optional video link           |
| created_at       | TIMESTAMP    | YES      | Auto-managed by Laravel       |
| updated_at       | TIMESTAMP    | YES      | Auto-managed by Laravel       |

---

## Models and Relationships

### StudentAchievementResultsModel

**Relationships:**

-   `category_part()` - BelongsTo relationship with CategoryPartsModel

**Accessors:**

-   `image_url` - Returns full URL to the image file

---

## Request Validation Classes

### AddStudentAchievementResultsRequest

-   Validates: category_part_id (required, exists), title (required, max 255), image (required, image file), video_link (optional, max 500)

### EditStudentAchievementResultsRequest

-   Validates: id (required, exists), category_part_id (optional, exists), title (optional, max 255), image (optional, image file), video_link (optional, max 500)

### DeleteStudentAchievementResultsRequest

-   Validates: id (required, exists)

---

## Notes

1. All endpoints require admin authentication via Bearer token
2. Images are stored in `storage/app/public/student_achievement_results/`
3. When updating with a new image, the old image is automatically deleted
4. When deleting a record, the associated image file is also deleted
5. Image uploads support: jpeg, png, jpg, gif, svg, webp formats (max 2MB)
6. All error messages are in Arabic for better user experience
