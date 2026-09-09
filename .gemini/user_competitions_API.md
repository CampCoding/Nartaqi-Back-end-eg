# User Competitions API Documentation

## Endpoint: `getAllCompetitions`

عرض جميع المسابقات النشطة للمستخدمين مع معلومات إضافية عن حالة كل مسابقة.

## Request Method

`GET`

## Route

```
/api/user/competitions/getAllCompetitions
```

## Authentication

❌ **لا يتطلب** مصادقة (متاح للجميع)

## Request Parameters

### Optional

- `per_page` (integer): عدد العناصر في كل صفحة (1-100، افتراضي: 10)
- `type` (string): نوع المسابقة (`daily`, `weekly`, `monthly`)

## Request Examples

### مثال 1: جلب كل المسابقات (افتراضي)

```http
GET /api/user/competitions/getAllCompetitions
```

### مثال 2: مع pagination

```http
GET /api/user/competitions/getAllCompetitions?per_page=20
```

### مثال 3: تصفية حسب النوع

```http
GET /api/user/competitions/getAllCompetitions?type=weekly
```

### مثال 4: تصفية مع pagination

```http
GET /api/user/competitions/getAllCompetitions?type=monthly&per_page=15
```

## Response

### Success Response (200)

```json
{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "competition_name": "مسابقة البرمجة الأسبوعية",
                "type": "weekly",
                "question_type": "multi",
                "idea": "اختبر مهاراتك في البرمجة أسبوعياً",
                "prize": "1000 جنيه",
                "start_date": "2026-01-20T00:00:00.000000Z",
                "end_date": "2026-01-26T23:59:59.000000Z",
                "active": true,
                "image": "competitions/weekly_prog.jpg",
                "image_url": "http://localhost/storage/competitions/weekly_prog.jpg",
                "created_at": "2026-01-17T10:00:00.000000Z",
                "updated_at": "2026-01-17T10:00:00.000000Z",

                // ✨ Computed Fields
                "questions_count": 14,
                "max_questions": 14,
                "status": "upcoming",
                "is_complete": true
            },
            {
                "id": 2,
                "competition_name": "تحدي الشهر الكبير",
                "type": "monthly",
                "question_type": "single",
                "idea": "مسابقة شهرية شاملة",
                "prize": "5000 جنيه",
                "start_date": "2026-01-15T00:00:00.000000Z",
                "end_date": "2026-02-14T23:59:59.000000Z",
                "active": true,
                "image": null,
                "image_url": null,
                "created_at": "2026-01-17T10:00:00.000000Z",
                "updated_at": "2026-01-17T10:00:00.000000Z",

                // ✨ Computed Fields
                "questions_count": 25,
                "max_questions": 30,
                "status": "ongoing",
                "is_complete": false
            },
            {
                "id": 3,
                "competition_name": "مسابقة يومية سريعة",
                "type": "daily",
                "question_type": "single",
                "idea": "سؤال يومي بسيط",
                "prize": "100 جنيه",
                "start_date": "2026-01-10T00:00:00.000000Z",
                "end_date": "2026-01-10T23:59:59.000000Z",
                "active": true,
                "image": "competitions/daily.jpg",
                "image_url": "http://localhost/storage/competitions/daily.jpg",
                "created_at": "2026-01-17T10:00:00.000000Z",
                "updated_at": "2026-01-17T10:00:00.000000Z",

                // ✨ Computed Fields
                "questions_count": 5,
                "max_questions": null,
                "status": "ended",
                "is_complete": true
            }
        ],
        "first_page_url": "http://localhost/api/user/competitions/getAllCompetitions?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "http://localhost/api/user/competitions/getAllCompetitions?page=3",
        "links": [...],
        "next_page_url": "http://localhost/api/user/competitions/getAllCompetitions?page=2",
        "path": "http://localhost/api/user/competitions/getAllCompetitions",
        "per_page": 10,
        "prev_page_url": null,
        "to": 10,
        "total": 25
    }
}
```

## Computed Fields

كل مسابقة تحتوي على حقول محسوبة إضافية:

| Field             | Type         | Description                                    |
| ----------------- | ------------ | ---------------------------------------------- |
| `questions_count` | integer      | عدد الأسئلة الحالية في المسابقة                |
| `max_questions`   | integer/null | الحد الأقصى للأسئلة (null للمسابقات اليومية)   |
| `status`          | string       | حالة المسابقة (`upcoming`, `ongoing`, `ended`) |
| `is_complete`     | boolean      | هل المسابقة مكتملة من حيث الأسئلة؟             |

### Status Values

| Status     | Description | When                                         |
| ---------- | ----------- | -------------------------------------------- |
| `upcoming` | قادمة       | التاريخ الحالي قبل `start_date`              |
| `ongoing`  | جارية       | التاريخ الحالي بين `start_date` و `end_date` |
| `ended`    | منتهية      | التاريخ الحالي بعد `end_date`                |

## Filtering & Pagination

### Filter by Type

```http
GET /api/user/competitions/getAllCompetitions?type=weekly

# تعيد فقط المسابقات الأسبوعية
```

### Pagination

```http
GET /api/user/competitions/getAllCompetitions?page=2&per_page=15

# الصفحة الثانية، 15 عنصر في الصفحة
```

## Error Responses

### 422 - Validation Error

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "per_page": ["The per page must be at least 1."],
        "type": ["The selected type is invalid."]
    }
}
```

### 500 - Server Error

```json
{
    "status": "error",
    "message": "فشل في جلب المسابقات",
    "error": "Error details..."
}
```

## Use Cases

### 1. عرض جميع المسابقات النشطة

```javascript
fetch("/api/user/competitions/getAllCompetitions")
    .then((res) => res.json())
    .then((data) => {
        console.log("المسابقات:", data.data.data);
    });
```

### 2. عرض المسابقات الأسبوعية فقط

```javascript
fetch("/api/user/competitions/getAllCompetitions?type=weekly")
    .then((res) => res.json())
    .then((data) => {
        console.log("المسابقات الأسبوعية:", data.data.data);
    });
```

### 3. عرض بناءً على الحالة

```javascript
fetch("/api/user/competitions/getAllCompetitions")
    .then((res) => res.json())
    .then((data) => {
        const competitions = data.data.data;

        const ongoing = competitions.filter((c) => c.status === "ongoing");
        const upcoming = competitions.filter((c) => c.status === "upcoming");
        const ended = competitions.filter((c) => c.status === "ended");

        console.log("جارية:", ongoing);
        console.log("قادمة:", upcoming);
        console.log("منتهية:", ended);
    });
```

## Frontend Example (React)

```jsx
import React, { useEffect, useState } from "react";

function CompetitionsList() {
    const [competitions, setCompetitions] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch("/api/user/competitions/getAllCompetitions?per_page=20")
            .then((res) => res.json())
            .then((data) => {
                setCompetitions(data.data.data);
                setLoading(false);
            });
    }, []);

    if (loading) return <div>جاري التحميل...</div>;

    return (
        <div className="competitions-grid">
            {competitions.map((comp) => (
                <div
                    key={comp.id}
                    className={`competition-card ${comp.status}`}
                >
                    {comp.image_url && (
                        <img src={comp.image_url} alt={comp.competition_name} />
                    )}

                    <h3>{comp.competition_name}</h3>
                    <p>{comp.idea}</p>

                    <div className="competition-info">
                        <span className="prize">🏆 {comp.prize}</span>
                        <span className={`status ${comp.status}`}>
                            {comp.status === "ongoing" && "🟢 جارية"}
                            {comp.status === "upcoming" && "🔵 قادمة"}
                            {comp.status === "ended" && "⚫ منتهية"}
                        </span>
                    </div>

                    <div className="questions-info">
                        <span>{comp.questions_count} سؤال</span>
                        {comp.max_questions && (
                            <span>/ {comp.max_questions}</span>
                        )}
                        {comp.is_complete && (
                            <span className="badge">✓ مكتملة</span>
                        )}
                    </div>

                    <div className="dates">
                        <small>
                            من:{" "}
                            {new Date(comp.start_date).toLocaleDateString("ar")}
                        </small>
                        <small>
                            إلى:{" "}
                            {new Date(comp.end_date).toLocaleDateString("ar")}
                        </small>
                    </div>
                </div>
            ))}
        </div>
    );
}
```

## Important Notes

### 📌 ملاحظات مهمة

- ✅ **تعرض فقط المسابقات النشطة** (`active = true`)
- ✅ **مرتبة حسب التاريخ** (الأحدث أولاً)
- ✅ **تحتوي على صورة URL** إذا كانت الصورة موجودة
- ✅ **حسابات تلقائية** للحالة وعدد الأسئلة
- ⚠️ **المسابقات غير النشطة مخفية** للمستخدمين

### 🎯 حقول مفيدة للـ UI

```javascript
// استخدم هذه الحقول لتحسين عرض المسابقات

// عرض Badge بناءً على الحالة
if (competition.status === "ongoing") {
    // أضف badge "جارية الآن" بلون أخضر
}

// عرض Progress Bar
const progress =
    (competition.questions_count / competition.max_questions) * 100;
// <ProgressBar value={progress} />

// تعطيل زر الاشتراك إذا انتهت
<button disabled={competition.status === "ended"}>
    {competition.status === "ended" ? "انتهت" : "اشترك الآن"}
</button>;
```

## Response Data Structure

```
📦 Response
 ├─ status: "success"
 └─ data: Paginated Collection
     ├─ current_page
     ├─ data: Array of Competitions
     │   ├─ [0]: Competition Object
     │   │   ├─ id
     │   │   ├─ competition_name
     │   │   ├─ type (daily/weekly/monthly)
     │   │   ├─ question_type (single/multi)
     │   │   ├─ idea
     │   │   ├─ prize
     │   │   ├─ start_date
     │   │   ├─ end_date
     │   │   ├─ active
     │   │   ├─ image
     │   │   ├─ image_url
     │   │   ├─ created_at
     │   │   ├─ updated_at
     │   │   ├─ ✨ questions_count
     │   │   ├─ ✨ max_questions
     │   │   ├─ ✨ status
     │   │   └─ ✨ is_complete
     │   └─ [1], [2], ...
     ├─ first_page_url
     ├─ last_page_url
     ├─ next_page_url
     ├─ prev_page_url
     ├─ per_page
     └─ total
```

## Testing with cURL

```bash
# Basic request
curl http://localhost:8000/api/user/competitions/getAllCompetitions

# With filters
curl "http://localhost:8000/api/user/competitions/getAllCompetitions?type=weekly&per_page=5"

# With pagination
curl "http://localhost:8000/api/user/competitions/getAllCompetitions?page=2"
```

## Related Endpoints

- Admin: `/api/admin/competitions/getAllCompetitions` - للإدارة (تعرض الكل)
- Admin: `/api/admin/competitions/getActiveCompetitions` - للإدارة (النشطة فقط)
- User: `/api/user/competitions/getAllCompetitions` - للمستخدمين (النشطة فقط) ← **هذا**

---

**آخر تحديث:** 2026-01-17  
**Controller:** `UserCompetitionController`  
**Method:** `getAllCompetitions`
