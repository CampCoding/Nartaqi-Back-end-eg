# API Documentation: Exam Summary & Analysis Report (تقرير ملخص تسليم الاختبار)

## Endpoint

**POST** `/api/user/rounds/exams/getExamSummaryReport`

---

## Description (الوصف)

يقوم هذا الـ API بإرجاع تقرير تحليلي وإحصائي شامل وشديد الدقة بعد تسليم الطالب للاختبار، وهو مطابقة كاملة للواجهة والشاشة المعروضة (الكارت الرئيسي للنتيجة، إحصائيات الإجابات، الأداء حسب مستوى الصعوبة، تفاصيل الأداء والأسئلة حسب الأقسام).

---

## Authentication (الموثقية)

- **Required:** Bearer Token (`Authentication` Middleware)
- **ملاحظة:** يمكن إرسال `student_id` اختيارياً في الـ Body، وفي حالة عدم إرساله يتم اعتماده تلقائياً من توكن الطالب المسجل.

---

## Request Parameters (مدخلات الطلب)

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `exam_id` | `integer` | **نعم** | رقم/معرف الاختبار (`id` من جدول `exams`) |
| `student_id` | `integer` | لا (اختياري) | رقم الطالب (إذا تم استدعاؤه من جهة المشرف أو التطبيق) |

### Request Example (مثال الطلب):

```json
{
  "exam_id": 1,
  "student_id": 5
}
```

---

## Response Success (200 OK) (مثال الاستجابة الناجحة)

```json
{
  "status": "success",
  "message": "تم جلب تقرير نتائج الاختبار بنجاح",
  "data": {
    "exam_info": {
      "exam_id": 1,
      "exam_title": "اختبار تجريبي 1",
      "exam_label": "اختبار تدريبي",
      "level": "مبتدئ",
      "success_percentage": "50"
    },
    "overall_result": {
      "score_percentage": "0%",
      "score_percentage_number": 0,
      "status_title": "راجع النقاط الأساسية ثم حاول مجددًا",
      "status_description": "النتيجة ليست حكماً نهائياً، استخدم خريطة الأخطاء بالأسهل، وابدأ من الأسئلة السهلة.",
      "total_questions": 2,
      "correct_answers": 0,
      "wrong_answers": 2,
      "unanswered": 0
    },
    "performance_by_difficulty": {
      "easy": {
        "level_key": "easy",
        "level_name": "مستوى سهل",
        "percentage": "0%",
        "percentage_number": 0,
        "subtitle": "0 صحيح من 0",
        "correct_count": 0,
        "wrong_count": 0,
        "unanswered_count": 0,
        "total_count": 0
      },
      "medium": {
        "level_key": "medium",
        "level_name": "مستوى متوسط",
        "percentage": "0%",
        "percentage_number": 0,
        "subtitle": "0 صحيح من 0",
        "correct_count": 0,
        "wrong_count": 0,
        "unanswered_count": 0,
        "total_count": 0
      },
      "hard": {
        "level_key": "hard",
        "level_name": "مستوى صعب",
        "percentage": "0%",
        "percentage_number": 0,
        "subtitle": "0 صحيح من 0",
        "correct_count": 0,
        "wrong_count": 0,
        "unanswered_count": 0,
        "total_count": 0
      },
      "unspecified": {
        "level_key": "unspecified",
        "level_name": "مستوى غير محدد",
        "percentage": "0%",
        "percentage_number": 0,
        "subtitle": "0 صحيح من 2",
        "correct_count": 0,
        "wrong_count": 2,
        "unanswered_count": 0,
        "total_count": 2
      }
    },
    "sections_performance": [
      {
        "section_id": 10,
        "section_title": "القسم الأول التجريبي",
        "section_description": null,
        "section_subtitle": "0 صحيحة من 1",
        "stats": {
          "correct_count": 0,
          "wrong_count": 1,
          "unanswered_count": 0,
          "total_questions": 1
        },
        "questions": [
          {
            "question_id": 101,
            "question_number_title": "السؤال الأول",
            "global_number": 1,
            "question_text": "ما هي القيمة المفترضة للمتغير X؟",
            "question_type": "mcq",
            "status": "wrong",
            "status_label": "إجابة خاطئة",
            "is_correct": false,
            "difficulty_level": "unspecified",
            "difficulty_label": "نوع غير محدد",
            "student_answer": "10",
            "correct_answer": "20",
            "explanation": "شرح الإجابة الصحيحة يوضح أن قيمة المتغير 20 ناتجة عن المعادلة...",
            "paragraph": null,
            "options": [
              {
                "id": 501,
                "option_text": "10",
                "is_correct": false,
                "question_explanation": ""
              },
              {
                "id": 502,
                "option_text": "20",
                "is_correct": true,
                "question_explanation": "شرح الإجابة الصحيحة..."
              }
            ]
          }
        ]
      }
    ]
  },
  "code": 200
}
```

---

## Response Fields Description (شرح حقول الاستجابة)

### 1. `overall_result` (الكارت الرئيسي أعلى الصفحة):
- `score_percentage`: النسبة المئوية للدرجة الكلية (مثل `"0%"`).
- `status_title`: العنوان الرئيسي والنص المشجع بناءً على الدرجة (مثال: `"راجع النقاط الأساسية ثم حاول مجددًا"`).
- `status_description`: النص التفصيلي لإرشاد الطالب للتحسين.
- `total_questions`: إجمالي عدد أسئلة الاختبار.
- `correct_answers`: عدد الإجابات الصحيحة.
- `wrong_answers`: عدد الإجابات الخاطئة.
- `unanswered`: عدد الأسئلة المتروكة بدون إجابة.

### 2. `performance_by_difficulty` (الأداء حسب مستوى الصعوبة):
يتم تحديد مستوى صعوبة كل سؤال **ديناميكياً** بناءً على نسبة الإجابات الصحيحة لجميع الطلاب الذين أتموا هذا السؤال:
- **مستوى سهل (`easy`):** إذا كانت نسبة الطلاب الذين أجابوا بشكل صحيح $\ge 65\%$.
- **مستوى صعب (`hard`):** إذا كانت نسبة الطلاب الذين أجابوا بشكل صحيح $\le 40\%$.
- **مستوى متوسط (`medium`):** إذا كانت نسبة الإجابات الصحيحة بين $40\%$ و $65\%$.
- **نوع غير محدد (`unspecified`):** في حال عدم وجود إجابات مسجلة للسؤال بعد من أي طالب، يتم الرجوع لمستوى السؤال/القسم الافتراضي.

يتكون من 4 مستويات (`easy`, `medium`, `hard`, `unspecified`):
- `percentage`: نسبة النجاح في هذا المستوى.
- `subtitle`: نص التوضيح السريع (مثل `"0 صحيح من 2"`).
- `correct_count`, `wrong_count`, `unanswered_count`, `total_count`: عدادات تفصيلية لكل مستوى.

### 3. `sections_performance` (تفاصيل الأداء حسب الأقسام والأسئلة):
قائمة بجميع أقسام الاختبار وفي كل قسم:
- `section_title`: اسم القسم.
- `section_subtitle`: ملخص القسم (مثل `"0 صحيحة من 1"`).
- `questions`: قائمة بجميع أسئلة القسم وتتضمن:
  - `question_number_title`: عنوان السؤال (مثل `"السؤال الأول"`).
  - `status`: حالة الإجابة (`"correct"`, `"wrong"`, `"unanswered"`).
  - `status_label`: التسمية المباشرة للحالة (`"إجابة صحيحة"`, `"إجابة خاطئة"`, `"غير مجاب"`).
  - `difficulty_label`: المسمى التوضيحي لمستوى الصعوبة (مثل `"نوع غير محدد"`).
  - `explanation`: شرح وتوضيح السؤال لزر "عرض الحل".
  - `options`: جميع خيارات السؤال مع بيان الإجابة الصحيحة وشرحها.

---

## Error Responses (استجابات الأخطاء)

### 400 Bad Request
```json
{
  "status": "error",
  "message": "معرف الطالب مطلوب",
  "data": null,
  "code": 400
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "الامتحان غير موجود",
  "data": null,
  "code": 404
}
```
