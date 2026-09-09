# Admin Rounds API Documentation

## 🔐 Admin Rounds Management APIs

All admin APIs return responses in Arabic and support full CRUD operations for rounds management.

---

## 📋 **1. Get All Rounds**

### **Endpoint**
```
GET /api/admin/rounds
```

### **Query Parameters**
| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Number of items per page (default: 10) |
| `page` | integer | Page number |
| `course_category_id` | integer | Filter by course category ID |
| `status` | integer | Filter by status (0=inactive, 1=active) |
| `search` | string | Search by round name |

### **Example Request**
```bash
GET /api/admin/rounds?per_page=20&course_category_id=1&status=1&search=programming
```

### **Response**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Programming Round 1",
        "description": "Basic programming concepts",
        "image": "rounds/programming1.jpg",
        "image_url": "http://127.0.0.1:8000/storage/rounds/programming1.jpg",
        "price": 100.00,
        "start_date": "2025-01-01",
        "end_date": "2025-01-31",
        "gender": "both",
        "for": "Beginners",
        "goal": "Learn programming basics",
        "active": 1,
        "course_category_id": 1,
        "course_categories": {
          "id": 1,
          "name": "Programming"
        }
      }
    ],
    "total": 50
  },
  "message": "تم جلب الجولات بنجاح",
  "status": 200
}
```

---

## 🎯 **2. Get Rounds by Course Category**

### **Endpoint**
```
GET /api/admin/rounds/category/{courseCategoryId}
```

### **Example Request**
```bash
GET /api/admin/rounds/category/1?per_page=10
```

### **Response**
```json
{
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 15
  },
  "message": "تم جلب جولات الفئة بنجاح",
  "status": 200
}
```

---

## ➕ **3. Create New Round**

### **Endpoint**
```
POST /api/admin/rounds
```

### **Request Body**
```json
{
  "name": "Advanced Programming",
  "description": "Advanced programming concepts and techniques",
  "image": "file_upload",
  "price": 200.00,
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "gender": "both",
  "for": "Intermediate developers",
  "goal": "Master advanced programming",
  "course_category_id": 1
}
```

### **Response**
```json
{
  "data": {
    "id": 2,
    "name": "Advanced Programming",
    "description": "Advanced programming concepts and techniques",
    "image": "rounds/advanced_programming.jpg",
    "image_url": "http://127.0.0.1:8000/storage/rounds/advanced_programming.jpg",
    "price": 200.00,
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "gender": "both",
    "for": "Intermediate developers",
    "goal": "Master advanced programming",
    "active": 1,
    "course_category_id": 1,
    "course_categories": {
      "id": 1,
      "name": "Programming"
    }
  },
  "message": "تم إنشاء الجولة بنجاح",
  "status": 201
}
```

---

## 👁️ **4. Get Single Round**

### **Endpoint**
```
GET /api/admin/rounds/{id}
```

### **Example Request**
```bash
GET /api/admin/rounds/1
```

### **Response**
```json
{
  "data": {
    "id": 1,
    "name": "Programming Round 1",
    "description": "Basic programming concepts",
    "image": "rounds/programming1.jpg",
    "image_url": "http://127.0.0.1:8000/storage/rounds/programming1.jpg",
    "price": 100.00,
    "start_date": "2025-01-01",
    "end_date": "2025-01-31",
    "gender": "both",
    "for": "Beginners",
    "goal": "Learn programming basics",
    "active": 1,
    "course_category_id": 1,
    "course_categories": {
      "id": 1,
      "name": "Programming"
    }
  },
  "message": "تم جلب الجولة بنجاح",
  "status": 200
}
```

---

## ✏️ **5. Update Round**

### **Endpoint**
```
PUT /api/admin/rounds/{id}
```

### **Request Body** (All fields optional)
```json
{
  "name": "Updated Programming Round",
  "description": "Updated description",
  "price": 150.00,
  "active": 1
}
```

### **Response**
```json
{
  "data": {
    "id": 1,
    "name": "Updated Programming Round",
    "description": "Updated description",
    "price": 150.00,
    "active": 1,
    ...
  },
  "message": "تم تحديث الجولة بنجاح",
  "status": 200
}
```

---

## 🗑️ **6. Delete Round**

### **Endpoint**
```
DELETE /api/admin/rounds/{id}
```

### **Example Request**
```bash
DELETE /api/admin/rounds/1
```

### **Response**
```json
{
  "message": "تم حذف الجولة بنجاح",
  "status": 200
}
```

---

## 🔄 **7. Toggle Round Status**

### **Endpoint**
```
PATCH /api/admin/rounds/{id}/toggle-status
```

### **Example Request**
```bash
PATCH /api/admin/rounds/1/toggle-status
```

### **Response**
```json
{
  "data": {
    "id": 1,
    "active": 0,
    ...
  },
  "message": "تم تغيير حالة الجولة إلى غير نشط",
  "status": 200
}
```

---

## ✅ **8. Activate Round**

### **Endpoint**
```
PATCH /api/admin/rounds/{id}/activate
```

### **Response**
```json
{
  "data": {
    "id": 1,
    "active": 1,
    ...
  },
  "message": "تم تفعيل الجولة بنجاح",
  "status": 200
}
```

---

## ❌ **9. Deactivate Round**

### **Endpoint**
```
PATCH /api/admin/rounds/{id}/deactivate
```

### **Response**
```json
{
  "data": {
    "id": 1,
    "active": 0,
    ...
  },
  "message": "تم إلغاء تفعيل الجولة بنجاح",
  "status": 200
}
```

---

## 🧪 **Testing Examples**

### **cURL Examples**

#### Get All Rounds
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/rounds" \
  -H "Accept: application/json"
```

#### Create Round
```bash
curl -X POST "http://127.0.0.1:8000/api/admin/rounds" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Round",
    "description": "Test description",
    "price": 100.00,
    "start_date": "2025-01-01",
    "end_date": "2025-01-31",
    "gender": "both",
    "for": "Test audience",
    "goal": "Test goal",
    "course_category_id": 1
  }'
```

#### Update Round
```bash
curl -X PUT "http://127.0.0.1:8000/api/admin/rounds/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Round Name",
    "price": 150.00
  }'
```

#### Delete Round
```bash
curl -X DELETE "http://127.0.0.1:8000/api/admin/rounds/1" \
  -H "Accept: application/json"
```

#### Toggle Status
```bash
curl -X PATCH "http://127.0.0.1:8000/api/admin/rounds/1/toggle-status" \
  -H "Accept: application/json"
```

---

## 📝 **Validation Rules**

### **Create Round**
- `name`: required, string, max 255 characters
- `description`: required, string
- `image`: optional, image file (jpeg, png, jpg, gif), max 2MB
- `price`: required, numeric, min 0
- `start_date`: required, date, after or equal to today
- `end_date`: required, date, after start_date
- `gender`: required, in (male, female, both)
- `for`: required, string, max 255 characters
- `goal`: required, string, max 255 characters
- `course_category_id`: required, exists in course_categories table

### **Update Round**
- All fields are optional
- Same validation rules as create when provided

---

## 🌐 **Browser Testing**

You can test these APIs directly in your browser:

1. **Get All Rounds**: `http://127.0.0.1:8000/api/admin/rounds`
2. **Get Rounds by Category**: `http://127.0.0.1:8000/api/admin/rounds/category/1`
3. **Get Single Round**: `http://127.0.0.1:8000/api/admin/rounds/1`

---

## ⚠️ **Error Responses**

### **404 Not Found**
```json
{
  "message": "الجولة غير موجودة",
  "status": 404
}
```

### **422 Validation Error**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["اسم الجولة مطلوب"],
    "price": ["السعر يجب أن يكون رقم"]
  }
}
```

---

## 🎯 **Key Features**

- ✅ **Full CRUD Operations** (Create, Read, Update, Delete)
- ✅ **Arabic Response Messages**
- ✅ **Image Upload Support**
- ✅ **Status Management** (Active/Inactive)
- ✅ **Filtering & Search**
- ✅ **Pagination Support**
- ✅ **Validation with Arabic Messages**
- ✅ **Relationship Loading** (with course categories)
