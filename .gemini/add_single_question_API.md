# Add Single Question API Documentation

## Endpoint: `addSingleQuestion`

إضافة سؤال واحد إلى المسابقة مع التحقق من الحد الأقصى للأسئلة بناءً على نوع المسابقة.

## Request Method

`POST`

## Route

```
/api/admin/competitions/addSingleQuestion
```

## Request Parameters

### Required

- `competition_id` (integer): معرف المسابقة
- `question_text` (string): نص السؤال
- `question_type` (string): نوع السؤال (mcq, t_f, etc.)
- `options` (array): خيارات السؤال
    - `option_text` (string, required): نص الخيار
    - `is_correct` (boolean, required): هل الخيار صحيح

## Validation Rules

### القيود بناءً على نوع المسابقة

#### 📅 المسابقة الأسبوعية (Weekly)

| نوع الأسئلة | الحد الأقصى |
| ----------- | ----------- |
| `single`    | 7 أسئلة     |
| `multi`     | 14 سؤال     |

#### 📅 المسابقة الشهرية (Monthly)

| نوع الأسئلة | الحد الأقصى |
| ----------- | ----------- |
| `single`    | 30 سؤال     |
| `multi`     | 60 سؤال     |

#### 📅 المسابقة اليومية (Daily)

- **لا يوجد حد أقصى** ✓

## Request Examples

### مثال 1: إضافة سؤال لمسابقة أسبوعية (single)

```json
POST /api/admin/competitions/addSingleQuestion
{
    "competition_id": 3,
    "question_text": "ما هي عاصمة مصر؟",
    "question_type": "mcq",
    "options": [
        {
            "option_text": "القاهرة",
            "is_correct": true
        },
        {
            "option_text": "الإسكندرية",
            "is_correct": false
        },
        {
            "option_text": "الجيزة",
            "is_correct": false
        }
    ]
}
```

### مثال 2: إضافة سؤال True/False

```json
{
    "competition_id": 5,
    "question_text": "PHP هي لغة برمجة من جانب الخادم",
    "question_type": "t_f",
    "options": [
        {
            "option_text": "صح",
            "is_correct": true
        },
        {
            "option_text": "خطأ",
            "is_correct": false
        }
    ]
}
```

## Response

### Success Response (201)

```json
{
    "status": "success",
    "message": "تم إضافة السؤال بنجاح",
    "data": {
        "id": 15,
        "competition_id": 3,
        "question_text": "ما هي عاصمة مصر؟",
        "question_type": "mcq",
        "show_date": null,
        "created_at": "2026-01-17T15:10:00.000000Z",
        "updated_at": "2026-01-17T15:10:00.000000Z",
        "options": [
            {
                "id": 45,
                "question_id": 15,
                "option_text": "القاهرة",
                "is_correct": true
            },
            {
                "id": 46,
                "question_id": 15,
                "option_text": "الإسكندرية",
                "is_correct": false
            },
            {
                "id": 47,
                "question_id": 15,
                "option_text": "الجيزة",
                "is_correct": false
            }
        ]
    },
    "remaining_slots": 5
}
```

### Error Responses

#### 400 - تجاوز الحد الأقصى للأسئلة

```json
{
    "status": "error",
    "message": "لا يمكن إضافة المزيد من الأسئلة. المسابقة الأسبوعية (فردي) لا تتعدى 7 سؤال. العدد الحالي: 7"
}
```

#### 404 - المسابقة غير موجودة

```json
{
    "status": "error",
    "message": "المسابقة غير موجودة"
}
```

#### 422 - خطأ في البيانات

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "question_text": ["The question text field is required."],
        "options": ["The options field is required."]
    }
}
```

## Implementation Flow

```
1. Validate Request Data ✓
   ├── competition_id
   ├── question_text
   ├── question_type
   └── options[]

2. Get Competition Details ✓
   ├── competition.type (daily/weekly/monthly)
   └── competition.question_type (single/multi)

3. Count Existing Questions ✓
   └── SELECT COUNT(*) FROM competition_question WHERE competition_id = ?

4. Determine Max Questions ✓
   ├── Weekly + Single = 7
   ├── Weekly + Multi = 14
   ├── Monthly + Single = 30
   ├── Monthly + Multi = 60
   └── Daily = No Limit

5. Check if Limit Exceeded ✓
   └── IF existing >= max THEN error

6. Create Question ✓
   └── INSERT INTO competition_question

7. Create Options ✓
   └── INSERT INTO competition_question_options (for each option)

8. Return Response ✓
   └── Question data + remaining_slots
```

## Validation Logic

### الكود المستخدم للتحقق:

```php
// Get competition details
$competition = CompetitionsModel::findOrFail($competitionId);
$competitionType = $competition->type; // daily, weekly, monthly
$competitionQuestionType = $competition->question_type; // single, multi

// Count existing questions
$existingQuestionsCount = CompetitionQuestionsModel::where('competition_id', $competitionId)->count();

// Determine max questions
$maxQuestions = null;

if ($competitionType === 'weekly') {
    $maxQuestions = ($competitionQuestionType === 'single') ? 7 : 14;
} elseif ($competitionType === 'monthly') {
    $maxQuestions = ($competitionQuestionType === 'single') ? 30 : 60;
}

// Check limit
if ($maxQuestions !== null && $existingQuestionsCount >= $maxQuestions) {
    return error_response();
}
```

## Scenarios Table

| Competition Type | Question Type | Max Questions | Example Use Case            |
| ---------------- | ------------- | ------------- | --------------------------- |
| Weekly           | single        | 7             | سؤال واحد كل يوم لمدة أسبوع |
| Weekly           | multi         | 14            | سؤالين كل يوم لمدة أسبوع    |
| Monthly          | single        | 30            | سؤال واحد كل يوم لمدة شهر   |
| Monthly          | multi         | 60            | سؤالين كل يوم لمدة شهر      |
| Daily            | single/multi  | ∞             | بدون قيود                   |

## Testing Examples

### Test Case 1: إضافة السؤال الأول

```json
Request:
{
    "competition_id": 10,
    "question_text": "What is PHP?",
    "question_type": "mcq",
    "options": [...]
}

Response:
{
    "status": "success",
    "remaining_slots": 6  // (7 - 0 - 1 = 6)
}
```

### Test Case 2: إضافة السؤال السابع (آخر سؤال مسموح)

```json
Request: {...}

Response:
{
    "status": "success",
    "remaining_slots": 0  // (7 - 6 - 1 = 0)
}
```

### Test Case 3: محاولة إضافة السؤال الثامن (يتجاوز الحد)

```json
Request: {...}

Response (Error 400):
{
    "status": "error",
    "message": "لا يمكن إضافة المزيد من الأسئلة. المسابقة الأسبوعية (فردي) لا تتعدى 7 سؤال. العدد الحالي: 7"
}
```

## Response Fields

| Field             | Type       | Description                         |
| ----------------- | ---------- | ----------------------------------- |
| `status`          | string     | حالة العملية (success/error)        |
| `message`         | string     | رسالة توضيحية                       |
| `data`            | object     | بيانات السؤال المُضاف مع خياراته    |
| `remaining_slots` | int/string | عدد الأسئلة المتبقية أو "غير محدود" |

## Database Tables

### competitions

```sql
id, competition_name, type, question_type, start_date, end_date
```

### competition_question

```sql
id, competition_id, question_text, question_type, show_date
```

### competition_question_options

```sql
id, question_id, option_text, is_correct
```

## Important Notes

⚠️ **تحذيرات مهمة:**

- يجب التأكد من أن `question_type` في جدول `competitions` محدد قبل البدء
- الحد الأقصى يتم التحقق منه قبل إضافة السؤال (not after)
- `remaining_slots` يحسب بعد إضافة السؤال الحالي
- المسابقات اليومية ليس لها حد أقصى

✅ **أفضل الممارسات:**

- تحقق من `remaining_slots` في الـ response لمعرفة المتبقي
- استخدم `question_type` الصحيح (mcq, t_f, etc.)
- تأكد من وجود خيار صحيح واحد على الأقل
- للمسابقات الأسبوعية والشهرية، اعرض تحذير للمستخدم عند اقتراب الحد الأقصى

## Related Endpoints

- `makeAutoGenerateQuestions` - إنشاء أسئلة تلقائيًا من بنك الأسئلة
- `updateComputationQuestions` - تعديل سؤال موجود
- `deleteComputationQuestions` - حذف سؤال
- `getComputationQuestions` - عرض أسئلة المسابقة

## Comparison with Auto-Generate

| Feature     | `addSingleQuestion` | `makeAutoGenerateQuestions` |
| ----------- | ------------------- | --------------------------- |
| Source      | Manual input        | Question bank               |
| Quantity    | One at a time       | Bulk (7, 14, 30, 60)        |
| Validation  | Same limits         | Same limits                 |
| `show_date` | Not set (NULL)      | Auto-calculated             |
| Use Case    | Custom questions    | Quick setup                 |

## Error Handling

المعالجة تتضمن:

1. **404**: المسابقة غير موجودة
2. **400**: تجاوز الحد الأقصى
3. **422**: بيانات غير صحيحة
4. **500**: خطأ في الخادم

## Version History

- **v1.0** - Initial implementation with validation
