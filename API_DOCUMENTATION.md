# Course Categories API Documentation

## Get Course Categories with Pagination

### Endpoint
```
GET /api/v1/course-categories
```

### Authentication
- Requires: `Bearer Token` (Sanctum authentication)

### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | 10 | Number of items per page (max: 100) |
| `page` | integer | 1 | Page number to retrieve |

### Example Requests

#### Basic Request (Default pagination)
```bash
GET /api/v1/course-categories
Authorization: Bearer {your_token}
```

#### Custom Pagination
```bash
GET /api/v1/course-categories?per_page=20&page=2
Authorization: Bearer {your_token}
```

### Response Format

#### Success Response (200)
```json
{
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Programming",
                "description": "Programming courses",
                "image": "programming.jpg",
                "status": "active",
                "image_url": "http://localhost/storage/programming.jpg",
                "created_at": "2025-01-01T00:00:00.000000Z",
                "updated_at": "2025-01-01T00:00:00.000000Z"
            },
            {
                "id": 2,
                "name": "Design",
                "description": "Design courses",
                "image": "design.jpg",
                "status": "active",
                "image_url": "http://localhost/storage/design.jpg",
                "created_at": "2025-01-01T00:00:00.000000Z",
                "updated_at": "2025-01-01T00:00:00.000000Z"
            }
        ],
        "first_page_url": "http://localhost/api/v1/course-categories?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://localhost/api/v1/course-categories?page=5",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://localhost/api/v1/course-categories?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://localhost/api/v1/course-categories?page=2",
                "label": "2",
                "active": false
            }
        ],
        "next_page_url": "http://localhost/api/v1/course-categories?page=2",
        "path": "http://localhost/api/v1/course-categories",
        "per_page": 10,
        "prev_page_url": null,
        "to": 10,
        "total": 50
    },
    "message": "Course categories retrieved successfully",
    "status": 200
}
```

### Usage Examples

#### JavaScript/Fetch
```javascript
// Basic request
fetch('/api/v1/course-categories', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Categories:', data.data.data);
    console.log('Current page:', data.data.current_page);
    console.log('Total pages:', data.data.last_page);
});

// With custom pagination
fetch('/api/v1/course-categories?per_page=20&page=2', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Page 2 with 20 items:', data.data.data);
});
```

#### PHP/cURL
```php
// Basic request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/v1/course-categories');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// With custom pagination
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/v1/course-categories?per_page=20&page=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);
```

#### Postman
1. Method: `GET`
2. URL: `http://localhost/api/v1/course-categories`
3. Headers:
   - `Authorization`: `Bearer {your_token}`
   - `Accept`: `application/json`
4. Query Parameters (optional):
   - `per_page`: `20`
   - `page`: `2`

### Notes
- The API returns data in the order they were created (no custom sorting)
- Maximum 100 items per page to prevent performance issues
- All responses include pagination metadata for easy navigation
- The `image_url` field provides the full URL to the category image
