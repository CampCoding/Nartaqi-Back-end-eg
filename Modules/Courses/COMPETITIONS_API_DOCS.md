# Competitions API Documentation

## Overview

This API provides CRUD operations for managing competitions in the system.

**Base URL:** `/api/admin/competitions`

**Authentication:** All endpoints require Admin authentication via `AdminAuthentication` middleware.

---

## Endpoints

### 1. Get All Competitions

Retrieve a paginated list of all competitions with optional filtering.

**Method:** `GET`  
**Endpoint:** `/api/admin/competitions/getAllCompetitions`

**Query Parameters:**

-   `per_page` (optional, integer, default: 10) - Number of items per page
-   `active` (optional, boolean) - Filter by active status (0 or 1)

**Request Example:**

```
GET /api/admin/competitions/getAllCompetitions?per_page=15&active=1
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
                "competition_name": "Annual Programming Contest",
                "type": "Programming",
                "idea": "Solve algorithmic challenges",
                "prize": "1000$",
                "start_date": "2026-02-01",
                "end_date": "2026-02-15",
                "active": true,
                "created_at": "2026-01-15T12:00:00.000000Z",
                "updated_at": "2026-01-15T12:00:00.000000Z"
            }
        ],
        "first_page_url": "...",
        "from": 1,
        "last_page": 1,
        "last_page_url": "...",
        "links": [...],
        "next_page_url": null,
        "path": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

---

### 2. Get Active Competitions Only

Retrieve only active competitions.

**Method:** `GET`  
**Endpoint:** `/api/admin/competitions/getActiveCompetitions`

**Query Parameters:**

-   `per_page` (optional, integer, default: 10) - Number of items per page

**Request Example:**

```
GET /api/admin/competitions/getActiveCompetitions?per_page=10
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
                "competition_name": "Annual Programming Contest",
                "type": "Programming",
                "idea": "Solve algorithmic challenges",
                "prize": "1000$",
                "start_date": "2026-02-01",
                "end_date": "2026-02-15",
                "active": true,
                "created_at": "2026-01-15T12:00:00.000000Z",
                "updated_at": "2026-01-15T12:00:00.000000Z"
            }
        ],
        ...
    }
}
```

---

### 3. Show Competition by ID

Retrieve a specific competition by its ID.

**Method:** `POST`  
**Endpoint:** `/api/admin/competitions/showCompetition`

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
        "competition_name": "Annual Programming Contest",
        "type": "Programming",
        "idea": "Solve algorithmic challenges",
        "prize": "1000$",
        "start_date": "2026-02-01",
        "end_date": "2026-02-15",
        "active": true,
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T12:00:00.000000Z"
    }
}
```

**Error Response (404):**

```json
{
    "status": "error",
    "message": "Competition not found"
}
```

---

### 4. Create New Competition

Create a new competition in the system.

**Method:** `POST`  
**Endpoint:** `/api/admin/competitions/storeCompetition`

**Request Body:**

```json
{
    "competition_name": "Annual Programming Contest",
    "type": "Programming",
    "idea": "Solve algorithmic challenges in multiple rounds",
    "prize": "1000$",
    "start_date": "2026-02-01",
    "end_date": "2026-02-15",
    "active": true
}
```

**Validation Rules:**

-   `competition_name` (required, string, max: 255)
-   `type` (required, string, max: 100)
-   `idea` (required, string)
-   `prize` (required, string, max: 255)
-   `start_date` (required, date)
-   `end_date` (required, date, must be equal to or after start_date)
-   `active` (optional, boolean, default: false)

**Success Response (201):**

```json
{
    "status": "success",
    "message": "Competition created successfully",
    "data": {
        "id": 1,
        "competition_name": "Annual Programming Contest",
        "type": "Programming",
        "idea": "Solve algorithmic challenges in multiple rounds",
        "prize": "1000$",
        "start_date": "2026-02-01",
        "end_date": "2026-02-15",
        "active": true,
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T12:00:00.000000Z"
    }
}
```

**Validation Error Response (422):**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "competition_name": ["Competition name is required"],
        "end_date": ["End date must be equal to or after the start date"]
    }
}
```

---

### 5. Update Competition

Update an existing competition.

**Method:** `POST`  
**Endpoint:** `/api/admin/competitions/updateCompetition`

**Request Body:**

```json
{
    "id": 1,
    "competition_name": "Updated Programming Contest",
    "type": "Programming",
    "idea": "Updated idea with new challenges",
    "prize": "1500$",
    "start_date": "2026-02-05",
    "end_date": "2026-02-20",
    "active": true
}
```

**Validation Rules:**
All fields are optional during update. Only provide fields you want to update.

**Success Response (200):**

```json
{
    "status": "success",
    "message": "Competition updated successfully",
    "data": {
        "id": 1,
        "competition_name": "Updated Programming Contest",
        "type": "Programming",
        "idea": "Updated idea with new challenges",
        "prize": "1500$",
        "start_date": "2026-02-05",
        "end_date": "2026-02-20",
        "active": true,
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T14:00:00.000000Z"
    }
}
```

**Error Response (404):**

```json
{
    "status": "error",
    "message": "Competition not found"
}
```

---

### 6. Delete Competition

Delete a competition from the system.

**Method:** `POST`  
**Endpoint:** `/api/admin/competitions/deleteCompetition`

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
    "message": "Competition deleted successfully"
}
```

**Error Response (404):**

```json
{
    "status": "error",
    "message": "Competition not found"
}
```

---

### 7. Toggle Competition Status

Toggle the active/inactive status of a competition.

**Method:** `POST`  
**Endpoint:** `/api/admin/competitions/toggleCompetitionStatus`

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
    "message": "Competition status updated successfully",
    "data": {
        "id": 1,
        "competition_name": "Annual Programming Contest",
        "type": "Programming",
        "idea": "Solve algorithmic challenges",
        "prize": "1000$",
        "start_date": "2026-02-01",
        "end_date": "2026-02-15",
        "active": false,
        "created_at": "2026-01-15T12:00:00.000000Z",
        "updated_at": "2026-01-15T14:18:00.000000Z"
    }
}
```

**Error Response (404):**

```json
{
    "status": "error",
    "message": "Competition not found"
}
```

---

## Error Responses

All endpoints may return the following error responses:

### 500 - Internal Server Error

```json
{
    "status": "error",
    "message": "Failed to [operation]",
    "error": "Detailed error message"
}
```

### 422 - Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

---

## Testing with Postman/Insomnia

### Headers Required

```
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

### Example cURL Commands

**Create Competition:**

```bash
curl -X POST http://your-domain.com/api/admin/competitions/storeCompetition \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "competition_name": "Annual Programming Contest",
    "type": "Programming",
    "idea": "Solve algorithmic challenges",
    "prize": "1000$",
    "start_date": "2026-02-01",
    "end_date": "2026-02-15",
    "active": true
  }'
```

**Get All Competitions:**

```bash
curl -X GET "http://your-domain.com/api/admin/competitions/getAllCompetitions?per_page=10&active=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Update Competition:**

```bash
curl -X POST http://your-domain.com/api/admin/competitions/updateCompetition \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "competition_name": "Updated Contest Name",
    "prize": "1500$"
  }'
```

**Delete Competition:**

```bash
curl -X POST http://your-domain.com/api/admin/competitions/deleteCompetition \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1
  }'
```

---

## Notes

1. All dates should be in `YYYY-MM-DD` format
2. The `active` field accepts boolean values (true/false or 1/0)
3. The `end_date` must be equal to or after the `start_date`
4. All endpoints require admin authentication
5. Pagination is available for list endpoints with default 10 items per page
6. Competitions are ordered by `start_date` in descending order (newest first)
7. **Image Upload**:
    - The `image` field is optional for both create and update operations
    - Accepted formats: jpeg, jpg, png, gif
    - Maximum file size: 2MB (2048 KB)
    - When uploading images, use `Content-Type: multipart/form-data` instead of `application/json`
    - Images are stored in `storage/app/public/competitions/` directory
    - Old images are automatically deleted when updating or deleting competitions
    - Image path is returned in the format: `competitions/timestamp_uniqueid.extension`

### Image Upload Example (Postman):

**Create Competition with Image:**

1. Set method to `POST`
2. URL: `http://your-domain.com/api/admin/competitions/storeCompetition`
3. Headers:
    - `Authorization: Bearer YOUR_ADMIN_TOKEN`
    - `Accept: application/json`
    - **Do NOT set** `Content-Type` (Postman will set it automatically)
4. Body → select `form-data`
5. Add fields:
    - `competition_name`: Annual Programming Contest
    - `type`: Programming
    - `idea`: Solve algorithmic challenges
    - `prize`: 1000$
    - `start_date`: 2026-02-01
    - `end_date`: 2026-02-15
    - `active`: 1
    - `image`: [Select File] → Choose image file

**Update Competition Image:**
Same as above but include the `id` field and only the fields you want to update.
