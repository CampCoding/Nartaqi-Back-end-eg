# Auto Generate Competition Questions - Implementation Summary

## Overview

تم تنفيذ طريقة `makeAutoGenerateQuestions` في `CompetitionsController` لإنشاء أسئلة المسابقات تلقائيًا من بنك الأسئلة مع توزيع التواريخ بناءً على نوع المسابقة.

## Request Parameters

```json
{
    "competition_id": 1,
    "questions_count": 14
}
```

## Competition Types Logic

### 1️⃣ Daily (يومي)

- **عدد الأسئلة**: أي عدد (لا يوجد قيود)
- **التوزيع**: حسب العدد المطلوب، سؤال واحد أو أكثر يوميًا

### 2️⃣ Weekly (أسبوعي)

- **عدد الأسئلة**: يجب أن يكون 7 أو 14 فقط
- **التوزيع**:
    - **7 أسئلة**: سؤال واحد كل يوم (7 أيام)
    - **14 سؤال**: سؤالين كل يوم (7 أيام)
- **تاريخ البداية**: يبدأ من `start_date` للمسابقة
- **التسلسل**: التاريخ يزيد يوميًا

### 3️⃣ Monthly (شهري)

- **عدد الأسئلة**: يجب أن يكون 30 أو 60 فقط
- **التوزيع**:
    - **30 سؤال**: سؤال واحد كل يوم (30 يوم)
    - **60 سؤال**: سؤالين كل يوم (30 يوم)
- **تاريخ البداية**: يبدأ من `start_date` للمسابقة
- **التسلسل**: التاريخ يزيد يوميًا

## Implementation Flow

### Step 1: Validation

```php
// التحقق من صحة البيانات المدخلة
$request->validate([
    'competition_id' => 'required|exists:competitions,id',
    'questions_count' => 'required|integer|min:1'
]);
```

### Step 2: Competition Type Validation

```php
// التحقق من عدد الأسئلة حسب نوع المسابقة
if ($competitionType === 'weekly' && !in_array($questionsCount, [7, 14])) {
    // رفض الطلب
}

if ($competitionType === 'monthly' && !in_array($questionsCount, [30, 60])) {
    // رفض الطلب
}
```

### Step 3: Fetch Random Questions

```php
// جلب أسئلة عشوائية من بنك الأسئلة مع خياراتها
$bankQuestions = QuestionsBankModel::with('options')
    ->inRandomOrder()
    ->limit($questionsCount)
    ->get();
```

### Step 4: Determine Questions Per Day

```php
$questionsPerDay = 1; // افتراضيًا سؤال واحد يوميًا

if ($competitionType === 'weekly' && $questionsCount === 14) {
    $questionsPerDay = 2; // سؤالين يوميًا
}

if ($competitionType === 'monthly' && $questionsCount === 60) {
    $questionsPerDay = 2; // سؤالين يوميًا
}
```

### Step 5: Copy Questions with Date Distribution

```php
$currentDate = Carbon::parse($startDate);
$questionCounter = 0;

foreach ($bankQuestions as $bankQuestion) {
    // 1. إنشاء السؤال في جدول المسابقة
    $competitionQuestion = CompetitionQuestionsModel::create([
        'competition_id' => $competitionId,
        'show_date' => $currentDate->format('Y-m-d'),
        'question_text' => $bankQuestion->question_text,
        'question_type' => $bankQuestion->question_type
    ]);

    // 2. نسخ جميع الخيارات
    foreach ($bankQuestion->options as $option) {
        CompetitionQuestionsOptionsModel::create([
            'question_id' => $competitionQuestion->id,
            'option_text' => $option->option_text,
            'is_correct' => $option->is_correct,
            'question_explanation' => null
        ]);
    }

    $questionCounter++;

    // 3. الانتقال لليوم التالي عند الوصول لحد الأسئلة اليومية
    if ($questionCounter % $questionsPerDay === 0) {
        $currentDate->addDay();
    }
}
```

## Response Format

### Success Response (201)

```json
{
    "status": "success",
    "message": "تم إنشاء الأسئلة بنجاح",
    "data": {
        "competition_id": 1,
        "competition_type": "weekly",
        "questions_count": 14,
        "questions_per_day": 2,
        "start_date": "2026-01-17",
        "end_date": "2026-01-23"
    }
}
```

### Error Responses

#### 400 - Invalid Questions Count for Weekly

```json
{
    "status": "error",
    "message": "المسابقة الأسبوعية يجب أن تحتوي على 7 أو 14 سؤال فقط"
}
```

#### 400 - Invalid Questions Count for Monthly

```json
{
    "status": "error",
    "message": "المسابقة الشهرية يجب أن تحتوي على 30 أو 60 سؤال فقط"
}
```

#### 400 - Insufficient Questions in Bank

```json
{
    "status": "error",
    "message": "عدد الأسئلة المتاحة في بنك الأسئلة غير كافٍ"
}
```

#### 404 - Competition Not Found

```json
{
    "status": "error",
    "message": "المسابقة غير موجودة"
}
```

#### 422 - Validation Error

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "competition_id": ["The competition id field is required."],
        "questions_count": ["The questions count must be at least 1."]
    }
}
```

## Database Tables

### 1. competitions

- `id`
- `competition_name`
- `type` (daily, weekly, monthly)
- `start_date` ← **used as base date**
- `end_date`
- `active`

### 2. questions_bank

- `id`
- `question_text`
- `question_type`

### 3. question_bank_options

- `id`
- `question_id`
- `option_text`
- `is_correct`

### 4. competition_question

- `id`
- `competition_id`
- `show_date` ← **calculated based on logic**
- `question_text`
- `question_type`

### 5. competition_question_options

- `id`
- `question_id`
- `option_text`
- `is_correct`
- `question_explanation`

## Examples

### Example 1: Weekly Competition with 7 Questions

```
Competition start_date: 2026-01-17

Questions Distribution:
- Question 1: 2026-01-17 (Friday)
- Question 2: 2026-01-18 (Saturday)
- Question 3: 2026-01-19 (Sunday)
- Question 4: 2026-01-20 (Monday)
- Question 5: 2026-01-21 (Tuesday)
- Question 6: 2026-01-22 (Wednesday)
- Question 7: 2026-01-23 (Thursday)
```

### Example 2: Weekly Competition with 14 Questions

```
Competition start_date: 2026-01-17

Questions Distribution:
- Questions 1-2: 2026-01-17 (Friday)
- Questions 3-4: 2026-01-18 (Saturday)
- Questions 5-6: 2026-01-19 (Sunday)
- Questions 7-8: 2026-01-20 (Monday)
- Questions 9-10: 2026-01-21 (Tuesday)
- Questions 11-12: 2026-01-22 (Wednesday)
- Questions 13-14: 2026-01-23 (Thursday)
```

### Example 3: Monthly Competition with 30 Questions

```
Competition start_date: 2026-02-01

Questions Distribution:
- Question 1: 2026-02-01
- Question 2: 2026-02-02
- ...
- Question 30: 2026-03-02
(1 question per day for 30 days)
```

### Example 4: Monthly Competition with 60 Questions

```
Competition start_date: 2026-02-01

Questions Distribution:
- Questions 1-2: 2026-02-01
- Questions 3-4: 2026-02-02
- ...
- Questions 59-60: 2026-03-02
(2 questions per day for 30 days)
```

## Model Updates

### CompetitionQuestionsModel.php

```php
// Added table name
protected $table = 'competition_question';
```

### CompetitionQuestionsOptionsModel.php

```php
// Added table name
protected $table = 'competition_question_options';
```

## Files Modified

1. ✅ `Modules/Courses/app/Http/Controllers/CompetitionsController.php`
    - Added `makeAutoGenerateQuestions()` method
    - Added use statements for required models

2. ✅ `Modules/Courses/app/Models/CompetitionQuestionsModel.php`
    - Added `protected $table` property

3. ✅ `Modules/Courses/app/Models/CompetitionQuestionsOptionsModel.php`
    - Added `protected $table` property

## Testing the API

### Using Postman or cURL

```bash
POST /api/competitions/auto-generate-questions
Content-Type: application/json

{
    "competition_id": 1,
    "questions_count": 14
}
```

### Test Cases

1. **Daily Competition** ✓
    - Test with any number of questions
2. **Weekly Competition** ✓
    - Test with 7 questions (1 per day)
    - Test with 14 questions (2 per day)
    - Test with invalid count (e.g., 10) - should fail
3. **Monthly Competition** ✓
    - Test with 30 questions (1 per day)
    - Test with 60 questions (2 per day)
    - Test with invalid count (e.g., 45) - should fail

4. **Edge Cases** ✓
    - Insufficient questions in bank
    - Invalid competition_id
    - Missing parameters

## Notes

- 🔄 Questions are selected **randomly** from the question bank
- 📅 Date distribution is **automatic** based on type and count
- ✅ All questions and options are **deep copied** (new IDs)
- 🔐 The method includes **comprehensive validation**
- 🌍 All error messages are in **Arabic**
- 📊 The response includes useful metadata (dates, counts, etc.)
