# Submit All Answers API Documentation

## ✨ إرسال جميع الإجابات دفعة واحدة

بدلاً من إرسال كل إجابة على حدة، يمكنك الآن إرسال جميع الإجابات مرة واحدة!

## Endpoint

```
POST /api/user/competitions/submitAllAnswers
```

## 📝 مثال على البيانات (Request Sample)

```json
{
    "student_id": 123,
    "competition_id": 5,
    "answers": [
        {
            "question_id": 12,
            "answer_text": "القاهرة",
            "correct_option_id": 48
        },
        {
            "question_id": 13,
            "answer_text": "PHP",
            "correct_option_id": 52
        },
        {
            "question_id": 14,
            "answer_text": "صح",
            "correct_option_id": 56
        },
        {
            "question_id": 15,
            "answer_text": "Laravel",
            "correct_option_id": 60
        }
    ]
}
```

## Request Parameters

| Parameter                     | Type    | Required | Description                           |
| ----------------------------- | ------- | -------- | ------------------------------------- |
| `student_id`                  | integer | ✅ Yes   | معرف الطالب                           |
| `competition_id`              | integer | ✅ Yes   | معرف المسابقة                         |
| `answers`                     | array   | ✅ Yes   | مصفوفة الإجابات (سؤال واحد على الأقل) |
| `answers[].question_id`       | integer | ✅ Yes   | معرف السؤال                           |
| `answers[].answer_text`       | string  | ✅ Yes   | نص الإجابة المختارة                   |
| `answers[].correct_option_id` | integer | ✅ Yes   | معرف الخيار المختار                   |

## ✅ Success Response (201)

```json
{
    "status": "success",
    "message": "تم حفظ جميع الإجابات بنجاح",
    "data": {
        "total_questions": 4,
        "correct_answers": 3,
        "wrong_answers": 1,
        "score": 75.0,
        "answers": [
            {
                "question_id": 12,
                "answer_id": 501,
                "is_correct": true
            },
            {
                "question_id": 13,
                "answer_id": 502,
                "is_correct": true
            },
            {
                "question_id": 14,
                "answer_id": 503,
                "is_correct": false
            },
            {
                "question_id": 15,
                "answer_id": 504,
                "is_correct": true
            }
        ]
    }
}
```

## 📊 أمثلة تفصيلية

### مثال 1: مسابقة يومية (5 أسئلة)

**Request:**

```json
POST /api/user/competitions/submitAllAnswers
{
    "student_id": 101,
    "competition_id": 3,
    "answers": [
        {
            "question_id": 10,
            "answer_text": "القاهرة",
            "correct_option_id": 41
        },
        {
            "question_id": 11,
            "answer_text": "PHP",
            "correct_option_id": 45
        },
        {
            "question_id": 12,
            "answer_text": "صح",
            "correct_option_id": 49
        },
        {
            "question_id": 13,
            "answer_text": "Laravel",
            "correct_option_id": 53
        },
        {
            "question_id": 14,
            "answer_text": "100",
            "correct_option_id": 57
        }
    ]
}
```

**Response:**

```json
{
    "status": "success",
    "message": "تم حفظ جميع الإجابات بنجاح",
    "data": {
        "total_questions": 5,
        "correct_answers": 4,
        "wrong_answers": 1,
        "score": 80.0,
        "answers": [...]
    }
}
```

### مثال 2: سؤال واحد فقط

**Request:**

```json
{
    "student_id": 101,
    "competition_id": 3,
    "answers": [
        {
            "question_id": 10,
            "answer_text": "القاهرة",
            "correct_option_id": 41
        }
    ]
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "total_questions": 1,
        "correct_answers": 1,
        "wrong_answers": 0,
        "score": 100.0,
        "answers": [
            {
                "question_id": 10,
                "answer_id": 601,
                "is_correct": true
            }
        ]
    }
}
```

## ❌ Error Responses

### 1. الطالب غير مسجل

**Response (403):**

```json
{
    "status": "error",
    "message": "يجب التسجيل في المسابقة أولاً"
}
```

### 2. سؤال غير موجود

**Response (404):**

```json
{
    "status": "error",
    "message": "السؤال رقم 12 غير موجود أو غير مرتبط بالمسابقة"
}
```

### 3. أجبت على السؤال مسبقاً

**Response (400):**

```json
{
    "status": "error",
    "message": "لقد أجبت على السؤال رقم 12 من قبل"
}
```

### 4. مصفوفة الإجابات فارغة

**Response (422):**

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "answers": ["The answers field must have at least 1 items."]
    }
}
```

## 🔄 سير العمل الكامل

### خطوة 1: جلب الأسئلة

```javascript
const response = await fetch("/api/user/competitions/getCompetitionQuestions", {
    method: "POST",
    body: JSON.stringify({
        student_id: 101,
        competition_id: 3,
    }),
});

const { data } = await response.json();
const questions = data.questions;

// questions: [
//   {id: 10, question_text: "...", options: [...]},
//   {id: 11, question_text: "...", options: [...]},
//   ...
// ]
```

### خطوة 2: الطالب يجيب على الأسئلة

```javascript
const userAnswers = [
    { questionId: 10, selectedOptionId: 41, selectedText: "القاهرة" },
    { questionId: 11, selectedOptionId: 45, selectedText: "PHP" },
    { questionId: 12, selectedOptionId: 49, selectedText: "صح" },
];
```

### خطوة 3: إرسال جميع الإجابات مرة واحدة

```javascript
const submitResponse = await fetch("/api/user/competitions/submitAllAnswers", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        student_id: 101,
        competition_id: 3,
        answers: userAnswers.map((ans) => ({
            question_id: ans.questionId,
            answer_text: ans.selectedText,
            correct_option_id: ans.selectedOptionId,
        })),
    }),
});

const result = await submitResponse.json();

if (result.status === "success") {
    console.log(`درجتك: ${result.data.score}%`);
    console.log(`إجابات صحيحة: ${result.data.correct_answers}`);
    console.log(`إجابات خاطئة: ${result.data.wrong_answers}`);
}
```

## 💡 Frontend Example (React)

```jsx
function CompetitionQuiz({ studentId, competitionId, questions }) {
    const [userAnswers, setUserAnswers] = useState({});
    const [submitted, setSubmitted] = useState(false);
    const [result, setResult] = useState(null);

    const handleSubmit = async () => {
        // بناء مصفوفة الإجابات
        const answers = questions.map((q) => ({
            question_id: q.id,
            answer_text: userAnswers[q.id]?.text,
            correct_option_id: userAnswers[q.id]?.optionId,
        }));

        const response = await fetch(
            "/api/user/competitions/submitAllAnswers",
            {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    student_id: studentId,
                    competition_id: competitionId,
                    answers,
                }),
            },
        );

        const data = await response.json();

        if (data.status === "success") {
            setResult(data.data);
            setSubmitted(true);
        }
    };

    return (
        <div>
            {!submitted ? (
                <>
                    {questions.map((q) => (
                        <Question
                            key={q.id}
                            question={q}
                            onAnswer={(optionId, text) => {
                                setUserAnswers((prev) => ({
                                    ...prev,
                                    [q.id]: { optionId, text },
                                }));
                            }}
                        />
                    ))}
                    <button onClick={handleSubmit}>إرسال الإجابات</button>
                </>
            ) : (
                <Result
                    score={result.score}
                    correct={result.correct_answers}
                    wrong={result.wrong_answers}
                    total={result.total_questions}
                />
            )}
        </div>
    );
}
```

## 🔒 الأمان والتحقق

### التحقيقات التي يتم إجراؤها:

1. ✅ **الطالب مسجل في المسابقة**
2. ✅ **كل سؤال ينتمي للمسابقة المحددة**
3. ✅ **لم يتم الإجابة على السؤال مسبقاً**
4. ✅ **الخيار المختار ينتمي للسؤال**
5. ✅ **استخدام Transaction** - إما تُحفظ كل الإجابات أو لا شيء

## ⚠️ ملاحظات مهمة

- 🔄 **Transaction Safety** - إذا فشل أي سؤال، يتم rollback لكل الإجابات
- 🚫 **لا يمكن الإجابة مرتين** - كل سؤال مرة واحدة فقط
- ✅ **حساب تلقائي للدرجة** - النظام يحسب الدرجة النهائية
- 📊 **إحصائيات فورية** - عدد الإجابات الصحيحة والخاطئة

## 🎯 الفوائد

مقارنة مع `storeAnswer` (سؤال واحد):

| Feature     | storeAnswer | submitAllAnswers |
| ----------- | ----------- | ---------------- |
| عدد الطلبات | N طلب       | طلب واحد ✓       |
| السرعة      | بطيء        | سريع ✓           |
| Transaction | لا          | نعم ✓            |
| حساب الدرجة | يدوي        | تلقائي ✓         |
| إحصائيات    | لا          | نعم ✓            |

---

**آخر تحديث:** 2026-01-17  
**Controller:** `UserCompetitionController`  
**Method:** `submitAllAnswers`
