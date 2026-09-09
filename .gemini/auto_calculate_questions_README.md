# Auto-Calculate Questions Count Feature

## ✨ التحديث الجديد

تم تحديث `makeAutoGenerateQuestions` ليحسب عدد الأسئلة تلقائياً بناءً على `question_type` من المسابقة!

## 🎯 كيف يعمل

### الآن `questions_count` أصبح **اختياري**

| نوع المسابقة | question_type | questions_count محسوب تلقائياً |
| ------------ | ------------- | ------------------------------ |
| weekly       | single        | 7                              |
| weekly       | multi         | 14                             |
| monthly      | single        | 30                             |
| monthly      | multi         | 60                             |
| daily        | any           | **يجب إدخاله يدوياً**          |

## 📝 طرق الاستخدام

### الطريقة 1: حساب تلقائي (مستحسن للأسبوعي والشهري)

```json
POST /api/admin/competitions/makeAutoGenerateQuestions
{
    "competition_id": 3
}
```

**النتيجة:**

- إذا كانت المسابقة `weekly` + `single` → سيتم إنشاء 7 أسئلة تلقائياً ✓
- إذا كانت `weekly` + `multi` → سيتم إنشاء 14 سؤال تلقائياً ✓
- إذا كانت `monthly` + `single` → سيتم إنشاء 30 سؤال تلقائياً ✓
- إذا كانت `monthly` + `multi` → سيتم إنشاء 60 سؤال تلقائياً ✓

### الطريقة 2: إدخال يدوي

```json
POST /api/admin/competitions/makeAutoGenerateQuestions
{
    "competition_id": 5,
    "questions_count": 14
}
```

**سيتم التحقق من التوافق:**

- ✅ إذا كانت `weekly` → يجب أن يكون 7 أو 14
- ✅ إذا كانت `monthly` → يجب أن يكون 30 أو 60
- ✅ إذا كانت `daily` → أي عدد مسموح

## 📊 أمثلة على Requests & Responses

### مثال 1: مسابقة أسبوعية (single) - حساب تلقائي

**Request:**

```json
{
    "competition_id": 10
}
```

**Response:**

```json
{
    "status": "success",
    "message": "تم إنشاء الأسئلة بنجاح",
    "data": {
        "competition_id": 10,
        "competition_type": "weekly",
        "competition_question_type": "single",
        "questions_count": 7,
        "questions_per_day": 1,
        "start_date": "2026-01-20",
        "end_date": "2026-01-26",
        "auto_calculated": true // ← تم الحساب تلقائياً!
    }
}
```

### مثال 2: مسابقة شهرية (multi) - حساب تلقائي

**Request:**

```json
{
    "competition_id": 15
}
```

**Response:**

```json
{
    "status": "success",
    "message": "تم إنشاء الأسئلة بنجاح",
    "data": {
        "competition_id": 15,
        "competition_type": "monthly",
        "competition_question_type": "multi",
        "questions_count": 60,
        "questions_per_day": 2,
        "start_date": "2026-02-01",
        "end_date": "2026-03-02",
        "auto_calculated": true // ← تم الحساب تلقائياً!
    }
}
```

### مثال 3: مسابقة يومية - يتطلب قيمة يدوية

**Request:**

```json
{
    "competition_id": 20
}
```

**Response (Error):**

```json
{
    "status": "error",
    "message": "المسابقات اليومية تتطلب تحديد عدد الأسئلة يدوياً",
    "hint": "يرجى إضافة حقل questions_count في الطلب"
}
```

**الحل - إضافة questions_count:**

```json
{
    "competition_id": 20,
    "questions_count": 5
}
```

### مثال 4: قيمة خاطئة لمسابقة أسبوعية

**Request:**

```json
{
    "competition_id": 10,
    "questions_count": 10 // ← خطأ! يجب أن يكون 7 أو 14
}
```

**Response (Error):**

```json
{
    "status": "error",
    "message": "المسابقة الأسبوعية يجب أن تحتوي على 7 أو 14 سؤال فقط",
    "hint": "المسابقة (single) يجب أن تحتوي على 7 سؤال"
}
```

### مثال 5: عدد أسئلة غير كافٍ في البنك

**Request:**

```json
{
    "competition_id": 15
    // سيحاول إنشاء 60 سؤال لكن البنك يحتوي على 40 فقط
}
```

**Response (Error):**

```json
{
    "status": "error",
    "message": "عدد الأسئلة المتاحة في بنك الأسئلة غير كافٍ",
    "details": {
        "required": 60,
        "available": 40
    }
}
```

## 🔍 منطق التحديد التلقائي

```php
// في الكود
if ($questionsCount === null) {
    // الحساب التلقائي
    if ($competitionType === 'weekly') {
        $questionsCount = ($competitionQuestionType === 'single') ? 7 : 14;
    } elseif ($competitionType === 'monthly') {
        $questionsCount = ($competitionQuestionType === 'single') ? 30 : 60;
    } else {
        // daily يتطلب إدخال يدوي
        return error('المسابقات اليومية تتطلب تحديد عدد الأسئلة يدوياً');
    }
}
```

## 📈 مقارنة قبل وبعد

### قبل التحديث ❌

```json
// كان يتطلب دائماً questions_count
{
    "competition_id": 3,
    "questions_count": 14 // مطلوب دائماً
}
```

### بعد التحديث ✅

```json
// 1. بدون questions_count (يحسب تلقائياً)
{
    "competition_id": 3
}

// 2. مع questions_count (للتحكم اليدوي)
{
    "competition_id": 3,
    "questions_count": 14
}
```

## ⚙️ حقل auto_calculated في Response

الآن الـ response يحتوي على حقل جديد:

```json
{
    "auto_calculated": true   // تم الحساب تلقائياً
    // أو
    "auto_calculated": false  // تم الإدخال يدوياً
}
```

## 🎨 توصيات الاستخدام

### للمسابقات الأسبوعية والشهرية

✅ **استخدم الحساب التلقائي:**

```json
{
    "competition_id": 10
}
```

- أسهل
- أقل عرضة للأخطاء
- يضمن الالتزام بالقيم الصحيحة

### للمسابقات اليومية

⚠️ **يجب إدخال القيمة يدوياً:**

```json
{
    "competition_id": 20,
    "questions_count": 5 // مطلوب
}
```

### للتحكم الكامل (أي نوع)

💡 **حدد القيمة يدوياً:**

```json
{
    "competition_id": 15,
    "questions_count": 60
}
```

## 🔄 سير العمل الموصى به

```
1️⃣ إنشاء المسابقة
   POST /api/admin/competitions/storeCompetition
   {
       "competition_name": "مسابقة الشهر",
       "type": "monthly",
       "question_type": "multi",  // ← مهم!
       ...
   }

2️⃣ إنشاء الأسئلة تلقائياً
   POST /api/admin/competitions/makeAutoGenerateQuestions
   {
       "competition_id": 15
       // لا حاجة لـ questions_count!
   }

3️⃣ التحقق من النتيجة
   ✓ تم إنشاء 60 سؤال تلقائياً
   ✓ موزعة على 30 يوم (2 سؤال/يوم)
```

## 📋 Validation Rules

| Field             | Type    | Required    | Notes             |
| ----------------- | ------- | ----------- | ----------------- |
| `competition_id`  | integer | ✅ Yes      | يجب أن يكون موجود |
| `questions_count` | integer | ❌ Optional | nullable الآن     |

## 🎯 الحالات الخاصة

### حالة 1: مسابقة بدون question_type

```json
Response Error:
{
    "status": "error",
    "message": "فشل في إنشاء الأسئلة",
    "error": "..."
}
```

**الحل:** تأكد من أن المسابقة تحتوي على `question_type`

### حالة 2: قيمة question_type غير صحيحة

يجب أن تكون: `single` أو `multi`

## ✨ المزايا الجديدة

1. ✅ **سهولة الاستخدام** - لا حاجة لتذكر الأعداد
2. ✅ **تقليل الأخطاء** - النظام يحسب القيمة الصحيحة
3. ✅ **مرونة** - لا يزال بإمكانك الإدخال يدوي
4. ✅ **رسائل خطأ محسّنة** - توضح القيمة المتوقعة
5. ✅ **شفافية** - حقل `auto_calculated` يوضح طريقة الحساب

## 📌 ملاحظات مهمة

- المسابقات اليومية **تتطلب** إدخال يدوي
- القيم الصحيحة للأسبوعي: 7 أو 14
- القيم الصحيحة للشهري: 30 أو 60
- حقل `question_type` **مطلوب** في جدول المسابقات

---

**آخر تحديث:** 2026-01-17  
**التحديث:** جعل questions_count اختيارياً مع حساب تلقائي
