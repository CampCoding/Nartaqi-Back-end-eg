# Auto-Calculate Show Date Feature

## ✨ الميزة الجديدة

عند إضافة سؤال جديد باستخدام `addSingleQuestion`، سيتم حساب `show_date` **تلقائياً** بناءً على:

1. التواريخ الموجودة
2. ملء الفجوات في التسلسل
3. عدد الأسئلة المطلوب في كل يوم

## 🎯 كيف يعمل

### للمسابقات الأسبوعية والشهرية

النظام يبحث عن **أول تاريخ ناقص** في التسلسل:

```
المسابقة: weekly, question_type: multi (2 سؤال/يوم)
start_date: 2026-08-13

الأسئلة الموجودة:
- 2026-08-13: سؤالين ✓ (ممتلئ)
- 2026-08-14: سؤال واحد ⚠️ (ناقص!)
- 2026-08-15: سؤالين ✓ (ممتلئ)

إضافة سؤال جديد:
→ سيتم إضافته في: 2026-08-14 ← لأنه ناقص سؤال!
```

### للمسابقات اليومية

النظام يضيف السؤال في **اليوم التالي** لآخر سؤال:

```
المسابقة: daily
آخر سؤال: 2026-08-15

إضافة سؤال جديد:
→ سيتم إضافته في: 2026-08-16
```

## 📊 أمثلة تفصيلية

### مثال 1: مسابقة أسبوعية (single) - ملء فجوة

**الوضع الحالي:**

```
Competition: weekly, question_type: single (1 سؤال/يوم)
start_date: 2026-08-13

الأسئلة الموجودة:
2026-08-13: Q1 ✓
2026-08-14: --- ❌ (فجوة!)
2026-08-15: Q2 ✓
2026-08-16: Q3 ✓
```

**Request:**

```json
POST /api/admin/competitions/addSingleQuestion
{
    "competition_id": 10,
    "question_text": "ما هي عاصمة مصر؟",
    "question_type": "mcq",
    "options": [...]
}
```

**Response:**

```json
{
    "status": "success",
    "message": "تم إضافة السؤال بنجاح",
    "data": {
        "id": 25,
        "competition_id": 10,
        "show_date": "2026-08-14",  // ← تم ملء الفجوة!
        "question_text": "ما هي عاصمة مصر؟",
        ...
    },
    "show_date": "2026-08-14",
    "remaining_slots": 3,
    "questions_per_day": 1
}
```

### مثال 2: مسابقة أسبوعية (multi) - ملء نصف يوم

**الوضع الحالي:**

```
Competition: weekly, question_type: multi (2 سؤال/يوم)
start_date: 2026-08-13

الأسئلة الموجودة:
2026-08-13: Q1, Q2 ✓✓ (ممتلئ)
2026-08-14: Q3 ⚠️ (يحتاج سؤال آخر!)
2026-08-15: Q4, Q5 ✓✓ (ممتلئ)
```

**Request:**

```json
{
    "competition_id": 15,
    "question_text": "What is PHP?",
    "question_type": "mcq",
    "options": [...]
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "show_date": "2026-08-14",  // ← تكملة اليوم الناقص!
        ...
    },
    "show_date": "2026-08-14",
    "remaining_slots": 8,
    "questions_per_day": 2
}
```

### مثال 3: مسابقة أسبوعية - كل التواريخ مكتملة

**الوضع الحالي:**

```
Competition: weekly, question_type: single
start_date: 2026-08-13

الأسئلة الموجودة:
2026-08-13: Q1 ✓
2026-08-14: Q2 ✓
2026-08-15: Q3 ✓
2026-08-16: Q4 ✓
2026-08-17: Q5 ✓
2026-08-18: Q6 ✓
```

**Request:**

```json
{
    "competition_id": 20,
    "question_text": "New question",
    "question_type": "mcq",
    "options": [...]
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "show_date": "2026-08-19",  // ← اليوم السابع (آخر يوم)
        ...
    },
    "show_date": "2026-08-19",
    "remaining_slots": 0,  // آخر سؤال!
    "questions_per_day": 1
}
```

### مثال 4: مسابقة يومية

**الوضع الحالي:**

```
Competition: daily
start_date: 2026-08-13

الأسئلة الموجودة:
2026-08-13: Q1
2026-08-14: Q2
2026-08-15: Q3
```

**Request:**

```json
{
    "competition_id": 25,
    "question_text": "Daily question",
    "question_type": "t_f",
    "options": [...]
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "show_date": "2026-08-16",  // ← اليوم التالي لآخر سؤال
        ...
    },
    "show_date": "2026-08-16",
    "remaining_slots": "غير محدود",
    "questions_per_day": 1
}
```

## 🔍 الخوارزمية

### للمسابقات الأسبوعية والشهرية:

```
1. جلب جميع التواريخ الموجودة مع عدد الأسئلة لكل تاريخ

2. بدءًا من start_date، تحقق من كل يوم:

   FOR each day from start_date to (start_date + maxDays):
       questions_on_this_date = COUNT(questions WHERE show_date = current_date)

       IF questions_on_this_date < questions_per_day:
           USE this_date  ← أول تاريخ ناقص!
           BREAK

       current_date++

3. إذا لم توجد فجوات، استخدم التاريخ التالي
```

### للمسابقات اليومية:

```
1. ابحث عن آخر سؤال:
   last_question = الأحدث من حيث show_date

2. إذا وُجد:
   new_date = last_question.show_date + 1 day

3. إذا لم يوجد:
   new_date = competition.start_date
```

## 📈 سيناريوهات الاستخدام

### سيناريو 1: حذف سؤال وإعادة إضافته

```
1. الوضع الأولي:
   2026-08-13: Q1, Q2
   2026-08-14: Q3, Q4
   2026-08-15: Q5, Q6

2. حذف Q4:
   2026-08-13: Q1, Q2
   2026-08-14: Q3 ⚠️ (ناقص!)
   2026-08-15: Q5, Q6

3. إضافة سؤال جديد:
   → سيذهب إلى 2026-08-14 ← ملء المكان الفارغ!
```

### سيناريو 2: ترتيب متقطع

```
الموجود:
2026-08-13: Q1 ✓
2026-08-14: --- ❌
2026-08-15: Q2 ✓
2026-08-16: --- ❌
2026-08-17: Q3 ✓

إضافة سؤال:
→ سيذهب إلى 2026-08-14 ← أول فجوة!

إضافة سؤال آخر:
→ سيذهب إلى 2026-08-16 ← ثاني فجوة!
```

## 💡 فوائد الميزة

✅ **ملء تلقائي للفجوات** - لا حاجة لحساب التواريخ يدوياً
✅ **ترتيب صحيح** - الأسئلة تُضاف بالتسلسل الصحيح
✅ **سهولة الصيانة** - حذف وإضافة بدون قلق من الترتيب
✅ **توزيع متوازن** - يحترم عدد الأسئلة المطلوب يومياً

## ⚙️ الحقول في Response

| Field               | Type       | Description                |
| ------------------- | ---------- | -------------------------- |
| `show_date`         | string     | التاريخ المحسوب للسؤال     |
| `remaining_slots`   | int/string | عدد الأسئلة المتبقية       |
| `questions_per_day` | int        | عدد الأسئلة المطلوب يومياً |

## 🎯 أمثلة مرئية

### مسابقة أسبوعية (single: 1/day)

```
start_date: 2026-08-13
max: 7 questions

Timeline:
13  14  15  16  17  18  19
[Q1][  ][Q2][Q3][  ][Q4][  ]
          ↑
   First empty slot

Add Q5 → Goes to 14
Add Q6 → Goes to 17
Add Q7 → Goes to 19
```

### مسابقة أسبوعية (multi: 2/day)

```
start_date: 2026-08-13
max: 14 questions

Timeline:
   13      14      15      16
[Q1,Q2] [Q3, ] [Q4,Q5] [Q6,Q7]
             ↑
   First incomplete day

Add Q8 → Goes to 14 (complete the day)
Add Q9, Q10 → Goes to 17 (new day)
```

## 🔄 سير العمل الموصى به

```
1️⃣ إنشاء المسابقة
2️⃣ إما:
   - استخدم makeAutoGenerateQuestions (للإنشاء الكامل)
   - أو استخدم addSingleQuestion (للإضافة التدريجية)
3️⃣ إذا حذفت سؤال، addSingleQuestion سيملأ المكان تلقائياً!
```

## ⚠️ ملاحظات مهمة

- `show_date` يُحسب **تلقائياً** - لا تحتاج لإرساله
- النظام يحترم `questions_per_day` بناءً على `question_type`
- الفجوات **دائماً** تُملأ أولاً قبل إضافة أيام جديدة
- المسابقات اليومية ليس لها حد أقصى للأيام

## 🎨 كود توضيحي

```javascript
// Frontend Example
async function addQuestion(competitionId, questionData) {
    const response = await fetch("/api/admin/competitions/addSingleQuestion", {
        method: "POST",
        body: JSON.stringify({
            competition_id: competitionId,
            ...questionData,
        }),
    });

    const result = await response.json();

    console.log(`السؤال أُضيف في: ${result.show_date}`);
    console.log(`متبقي: ${result.remaining_slots} سؤال`);
    console.log(`الأسئلة يومياً: ${result.questions_per_day}`);
}
```

---

**آخر تحديث:** 2026-01-17  
**الميزة:** حساب تلقائي لـ show_date مع ملء الفجوات
