# Store Competition Answer API Documentation

## 📝 مثال على البيانات المرسلة (Sample Request Data)

### Endpoint

```
POST /api/user/competitions/storeAnswer
```

### Request Format

```json
{
    "student_id": 123,
    "competition_id": 5,
    "question_id": 45,
    "answer_text": "القاهرة",
    "correct_option_id": 178
}
```

### Request Parameters

| Parameter           | Type    | Required | Description         |
| ------------------- | ------- | -------- | ------------------- |
| `student_id`        | integer | ✅ Yes   | معرف الطالب         |
| `competition_id`    | integer | ✅ Yes   | معرف المسابقة       |
| `question_id`       | integer | ✅ Yes   | معرف السؤال         |
| `answer_text`       | string  | ✅ Yes   | نص الإجابة المختارة |
| `correct_option_id` | integer | ✅ Yes   | معرف الخيار المختار |

## 📊 أمثلة تفصيلية

### مثال 1: إجابة صحيحة

**Request:**

```json
POST /api/user/competitions/storeAnswer
{
    "student_id": 101,
    "competition_id": 3,
    "question_id": 12,
    "answer_text": "القاهرة",
    "correct_option_id": 48
}
```

**Response (201):**

```json
{
    "status": "success",
    "message": "تم تسجيل الإجابة بنجاح",
    "data": {
        "answer_id": 567,
        "question_id": 12,
        "competition_id": 3,
        "answer_text": "القاهرة",
        "correct_option_id": 48,
        "correct_or_not": 1 // ✓ إجابة صحيحة
    }
}
```

### مثال 2: إجابة خاطئة

**Request:**

```json
{
    "student_id": 101,
    "competition_id": 3,
    "question_id": 13,
    "answer_text": "الإسكندرية",
    "correct_option_id": 52
}
```

**Response (201):**

```json
{
    "status": "success",
    "message": "تم تسجيل الإجابة بنجاح",
    "data": {
        "answer_id": 568,
        "question_id": 13,
        "competition_id": 3,
        "answer_text": "الإسكندرية",
        "correct_option_id": 52,
        "correct_or_not": 0 // ✗ إجابة خاطئة
    }
}
```

### مثال 3: سؤال True/False

**Request:**

```json
{
    "student_id": 101,
    "competition_id": 3,
    "question_id": 14,
    "answer_text": "صح",
    "correct_option_id": 56
}
```

**Response (201):**

```json
{
    "status": "success",
    "message": "تم تسجيل الإجابة بنجاح",
    "data": {
        "answer_id": 569,
        "question_id": 14,
        "competition_id": 3,
        "answer_text": "صح",
        "correct_option_id": 56,
        "correct_or_not": 1
    }
}
```

## ❌ Error Responses

### 1. الطالب غير مسجل في المسابقة

**Response (403):**

```json
{
    "status": "error",
    "message": "يجب التسجيل في المسابقة أولاً"
}
```

### 2. السؤال غير موجود

**Response (404):**

```json
{
    "status": "error",
    "message": "السؤال غير موجود أو غير مرتبط بالمسابقة"
}
```

### 3. بيانات ناقصة

**Response (422):**

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "student_id": ["The student id field is required."],
        "question_id": ["The question id field is required."]
    }
}
```

## 🔄 سير العمل الكامل

### خطوة 1: الحصول على الأسئلة

```json
POST /api/user/competitions/getCompetitionQuestions
{
    "student_id": 101,
    "competition_id": 3
}

Response:
{
    "data": {
        "questions": [
            {
                "id": 12,
                "question_text": "ما هي عاصمة مصر؟",
                "options": [
                    {"id": 48, "option_text": "القاهرة", "is_correct": true},
                    {"id": 49, "option_text": "الإسكندرية", "is_correct": false},
                    {"id": 50, "option_text": "الجيزة", "is_correct": false}
                ]
            }
        ]
    }
}
```

### خطوة 2: إرسال الإجابة

```json
POST /api/user/competitions/storeAnswer
{
    "student_id": 101,
    "competition_id": 3,
    "question_id": 12,           // ← من السؤال
    "answer_text": "القاهرة",     // ← نص الخيار المختار
    "correct_option_id": 48      // ← id الخيار المختار
}
```

## 💡 Frontend Example (JavaScript)

```javascript
// بعد الحصول على الأسئلة
const question = questions[0];
const selectedOption = question.options[0]; // الطالب اختار الخيار الأول

// إرسال الإجابة
async function submitAnswer(
    studentId,
    competitionId,
    question,
    selectedOption,
) {
    const response = await fetch("/api/user/competitions/storeAnswer", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            student_id: studentId, // 101
            competition_id: competitionId, // 3
            question_id: question.id, // 12
            answer_text: selectedOption.option_text, // "القاهرة"
            correct_option_id: selectedOption.id, // 48
        }),
    });

    const result = await response.json();

    if (result.status === "success") {
        if (result.data.correct_or_not === 1) {
            console.log("✓ إجابة صحيحة!");
        } else {
            console.log("✗ إجابة خاطئة");
        }
    }
}
```

## 📋 Validation Rules

```php
'student_id' => 'required|exists:students,id'
'competition_id' => 'required|exists:competitions,id'
'question_id' => 'required|exists:competition_questions,id'
'answer_text' => 'required'
'correct_option_id' => 'required|exists:competition_question_options,id'
```

## 🎯 Use Cases

### Case 1: MCQ (Multiple Choice)

```json
{
    "question_id": 12,
    "answer_text": "القاهرة", // نص الخيار
    "correct_option_id": 48 // ID الخيار
}
```

### Case 2: True/False

```json
{
    "question_id": 14,
    "answer_text": "صح", // أو "خطأ"
    "correct_option_id": 56 // ID لـ "صح"
}
```

### Case 3: Short Answer (نص قصير)

```json
{
    "question_id": 15,
    "answer_text": "Cairo",
    "correct_option_id": 60
}
```

## ⚠️ Important Notes

1. ⚠️ **لا يمكن الإجابة مرتين** - كل سؤال يُجاب عليه مرة واحدة فقط
2. ✅ **الطالب يجب أن يكون مسجل** في المسابقة
3. ✅ **السؤال يجب أن يكون** جزء من المسابقة المحددة
4. ✅ **النظام يفحص تلقائياً** إذا كانت الإجابة صحيحة أم لا
5. 🔒 **البيانات محمية** - التحقق من صحة جميع المعطيات

## 🗄️ Database Structure

```sql
student_competition_answers
├── id
├── student_id
├── competition_id
├── question_id
├── option_id (correct_option_id)
├── is_correct (correct_or_not)
└── answered_at
```

---

**آخر تحديث:** 2026-01-17  
**Controller:** `UserCompetitionController`  
**Method:** `storeAnswer`
