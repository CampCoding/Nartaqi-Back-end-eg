# Enhanced Error Messages & Remaining Slots

## ✅ تحسينات تم إضافتها

### 1️⃣ رسائل خطأ محسّنة (Form Request)

تم إنشاء `AddCompetitionQuestionRequest` مع رسائل خطأ واضحة بالعربية:

#### مثال على رسائل الخطأ الجديدة:

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "competition_id": ["معرف المسابقة مطلوب"],
        "question_text": ["نص السؤال يجب أن يكون 5 أحرف على الأقل"],
        "question_type": [
            "نوع السؤال يجب أن يكون أحد القيم التالية: mcq, t_f, essay, short_answer"
        ],
        "options": ["يجب إضافة خيارين على الأقل للسؤال"]
    }
}
```

### 2️⃣ عرض الأسئلة المتبقية في getAllCompetitions

الآن عند طلب قائمة المسابقات، سيتم إضافة معلومات إضافية لكل مسابقة:

#### مثال على الـ Response:

```json
GET /api/admin/competitions/getAllCompetitions?per_page=5

Response:
{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "competition_name": "مسابقة البرمجة الأسبوعية",
                "type": "weekly",
                "question_type": "single",
                "idea": "مسابقة أسبوعية في البرمجة",
                "prize": "1000 جنيه",
                "start_date": "2026-01-20",
                "end_date": "2026-01-26",
                "active": true,
                "image": "competitions/image.jpg",
                "image_url": "http://localhost/storage/competitions/image.jpg",
                "created_at": "2026-01-17T15:00:00.000000Z",
                "updated_at": "2026-01-17T15:00:00.000000Z",

                // ✨ New Fields
                "remaining_slots": 4,
                "max_questions": 7,
                "current_questions_count": 3
            },
            {
                "id": 2,
                "competition_name": "مسابقة الشهر",
                "type": "monthly",
                "question_type": "multi",
                "idea": "مسابقة شهرية شاملة",
                "prize": "5000 جنيه",
                "start_date": "2026-02-01",
                "end_date": "2026-02-28",
                "active": true,
                "image": null,
                "image_url": null,
                "created_at": "2026-01-17T15:00:00.000000Z",
                "updated_at": "2026-01-17T15:00:00.000000Z",

                // ✨ New Fields
                "remaining_slots": 60,
                "max_questions": 60,
                "current_questions_count": 0
            },
            {
                "id": 3,
                "competition_name": "مسابقة اليومية",
                "type": "daily",
                "question_type": "single",
                "idea": "مسابقة يومية مفتوحة",
                "prize": "100 جنيه",
                "start_date": "2026-01-17",
                "end_date": "2026-01-17",
                "active": true,
                "image": null,
                "image_url": null,
                "created_at": "2026-01-17T15:00:00.000000Z",
                "updated_at": "2026-01-17T15:00:00.000000Z",

                // ✨ New Fields
                "remaining_slots": "غير محدود",
                "max_questions": null,
                "current_questions_count": 15
            }
        ],
        "first_page_url": "http://localhost/api/admin/competitions/getAllCompetitions?page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://localhost/api/admin/competitions/getAllCompetitions?page=2",
        "next_page_url": "http://localhost/api/admin/competitions/getAllCompetitions?page=2",
        "path": "http://localhost/api/admin/competitions/getAllCompetitions",
        "per_page": 5,
        "prev_page_url": null,
        "to": 5,
        "total": 10
    }
}
```

## 📊 الحقول الجديدة

| Field                     | Type       | Description                                                           |
| ------------------------- | ---------- | --------------------------------------------------------------------- |
| `remaining_slots`         | int/string | عدد الأسئلة المتبقية التي يمكن إضافتها. "غير محدود" للمسابقات اليومية |
| `max_questions`           | int/null   | الحد الأقصى للأسئلة المسموح بها. `null` للمسابقات اليومية             |
| `current_questions_count` | int        | عدد الأسئلة الحالية في المسابقة                                       |

## 🎯 حالات الاستخدام

### Case 1: مسابقة أسبوعية (single) - لديها 3 أسئلة

```json
{
    "type": "weekly",
    "question_type": "single",
    "remaining_slots": 4, // 7 - 3 = 4
    "max_questions": 7,
    "current_questions_count": 3
}
```

### Case 2: مسابقة شهرية (multi) - فارغة

```json
{
    "type": "monthly",
    "question_type": "multi",
    "remaining_slots": 60, // 60 - 0 = 60
    "max_questions": 60,
    "current_questions_count": 0
}
```

### Case 3: مسابقة أسبوعية (multi) - ممتلئة

```json
{
    "type": "weekly",
    "question_type": "multi",
    "remaining_slots": 0, // 14 - 14 = 0 (المسابقة ممتلئة!)
    "max_questions": 14,
    "current_questions_count": 14
}
```

### Case 4: مسابقة يومية

```json
{
    "type": "daily",
    "question_type": "single",
    "remaining_slots": "غير محدود", // لا يوجد حد أقصى
    "max_questions": null,
    "current_questions_count": 25
}
```

## 💡 استخدام البيانات في الـ Frontend

### مثال React/Vue Component

```javascript
// عرض المسابقات مع حالة الامتلاء
competitions.map((competition) => {
    const isFull = competition.remaining_slots === 0;
    const isUnlimited = competition.remaining_slots === "غير محدود";

    return (
        <div className={`competition-card ${isFull ? "full" : ""}`}>
            <h3>{competition.competition_name}</h3>

            {!isUnlimited && (
                <div className="progress-bar">
                    <div
                        className="fill"
                        style={{
                            width: `${(competition.current_questions_count / competition.max_questions) * 100}%`,
                        }}
                    />
                    <span>
                        {competition.current_questions_count} /{" "}
                        {competition.max_questions}
                    </span>
                </div>
            )}

            <div className="slots-info">
                {isFull ? (
                    <span className="badge full">ممتلئة ✓</span>
                ) : isUnlimited ? (
                    <span className="badge unlimited">غير محدود ∞</span>
                ) : (
                    <span className="badge available">
                        متبقي: {competition.remaining_slots} سؤال
                    </span>
                )}
            </div>

            <button
                disabled={isFull}
                onClick={() => addQuestion(competition.id)}
            >
                {isFull ? "لا يمكن الإضافة" : "إضافة سؤال"}
            </button>
        </div>
    );
});
```

## 🔍 منطق الحساب

```php
// في الكود
if ($competition->type === 'weekly') {
    $maxQuestions = ($competition->question_type === 'single') ? 7 : 14;
} elseif ($competition->type === 'monthly') {
    $maxQuestions = ($competition->question_type === 'single') ? 30 : 60;
} else {
    $maxQuestions = null; // Daily = unlimited
}

$remainingSlots = $maxQuestions !== null
    ? max(0, $maxQuestions - $existingQuestionsCount)
    : 'غير محدود';
```

## ⚡ الأداء

- يتم حساب `remaining_slots` ديناميكيًا عند كل طلب
- يتم استخدام `transform()` على الـ collection بعد الـ pagination
- لا يؤثر على عدد الـ queries (query واحد لكل مسابقة لعد الأسئلة)

## 📝 ملاحظات مهمة

✅ **مميزات:**

- معلومات فورية عن حالة المسابقة
- سهولة معرفة ما إذا كانت المسابقة ممتلئة
- يمكن استخدام البيانات مباشرة في الـ UI

⚠️ **تحذيرات:**

- البيانات ديناميكية (يتم حسابها في كل طلب)
- تأكد من وجود `question_type` في جدول `competitions`
- المسابقات اليومية ترجع "غير محدود" كنص وليس رقم

## 🎨 تحسينات Form Request

### رسائل محددة لكل خطأ:

```php
// في AddCompetitionQuestionRequest.php
'competition_id.exists' => 'المسابقة المحددة غير موجودة في النظام'
'question_text.min' => 'نص السؤال يجب أن يكون 5 أحرف على الأقل'
'question_type.in' => 'نوع السؤال يجب أن يكون أحد القيم التالية: mcq, t_f, essay, short_answer'
'options.min' => 'يجب إضافة خيارين على الأقل للسؤال'
```

## الملفات المحدثة

1. ✅ `CompetitionsController.php` - تحديث `getAllCompetitions()`
2. ✅ `AddCompetitionQuestionRequest.php` - رسائل خطأ محسّنة
3. ✅ الآن كل مسابقة تحتوي على `remaining_slots`, `max_questions`, `current_questions_count`

---

**آخر تحديث:** 2026-01-17
