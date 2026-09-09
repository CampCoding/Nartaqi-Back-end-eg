# Competition Questions API - Complete Reference

## Overview

Complete CRUD operations for managing competition questions in the system.

---

## 📋 API Endpoints Summary

| Endpoint                   | Method | Purpose                             | Route                                                |
| -------------------------- | ------ | ----------------------------------- | ---------------------------------------------------- |
| makeAutoGenerateQuestions  | POST   | Create questions from question bank | `/api/admin/competitions/makeAutoGenerateQuestions`  |
| updateComputationQuestions | POST   | Update question text and options    | `/api/admin/competitions/updateComputationQuestions` |
| deleteComputationQuestions | POST   | Delete question and its options     | `/api/admin/competitions/deleteComputationQuestions` |

---

## 1️⃣ CREATE: Auto-Generate Questions

### Purpose

Copy questions from the question bank to a competition with automatic date distribution.

### Request

```json
POST /api/admin/competitions/makeAutoGenerateQuestions
{
    "competition_id": 3,
    "questions_count": 14
}
```

### Rules by Competition Type

- **Daily**: Any number of questions
- **Weekly**: Must be 7 or 14 questions
- **Monthly**: Must be 30 or 60 questions

### Date Distribution

- **7 or 30 questions**: 1 question per day
- **14 or 60 questions**: 2 questions per day

### Response

```json
{
    "status": "success",
    "message": "تم إنشاء الأسئلة بنجاح",
    "data": {
        "competition_id": 3,
        "competition_type": "weekly",
        "questions_count": 14,
        "questions_per_day": 2,
        "start_date": "2026-01-17",
        "end_date": "2026-01-23"
    }
}
```

### Key Features

✅ Random selection from question bank  
✅ Automatic date distribution  
✅ Deep copy (new IDs for questions and options)  
✅ Validation based on competition type

---

## 2️⃣ UPDATE: Update Question

### Purpose

Update an existing competition question's text and/or options.

### Request Examples

**Update Text Only:**

```json
POST /api/admin/competitions/updateComputationQuestions
{
    "id": 5,
    "question_text": "What is the capital of Egypt?"
}
```

**Update Options Only:**

```json
{
    "id": 5,
    "options": [
        { "option_text": "Cairo", "is_correct": true },
        { "option_text": "Alexandria", "is_correct": false },
        { "option_text": "Giza", "is_correct": false }
    ]
}
```

**Update Both:**

```json
{
    "id": 5,
    "question_text": "What is the capital of Egypt?",
    "options": [
        { "option_text": "Cairo", "is_correct": true },
        { "option_text": "Alexandria", "is_correct": false }
    ]
}
```

### Response

```json
{
    "status": "success",
    "message": "تم تحديث السؤال بنجاح",
    "data": {
        "id": 5,
        "competition_id": 3,
        "show_date": "2026-08-13",
        "question_text": "What is the capital of Egypt?",
        "question_type": "mcq",
        "questions": [
            {
                "id": 15,
                "option_text": "Cairo",
                "is_correct": true
            },
            {
                "id": 16,
                "option_text": "Alexandria",
                "is_correct": false
            }
        ]
    }
}
```

### Key Features

✅ Partial updates (text OR options OR both)  
✅ Complete option replacement  
✅ Database transactions  
✅ Returns updated question with options

⚠️ **Important:** When updating options, ALL old options are deleted and replaced with new ones.

---

## 3️⃣ DELETE: Delete Question

### Purpose

Delete a competition question and all its associated options.

### Request

```json
POST /api/admin/competitions/deleteComputationQuestions
{
    "id": 5
}
```

### Response

```json
{
    "status": "success",
    "message": "تم حذف السؤال بنجاح"
}
```

### Deletion Order

1. Delete all question options (`competition_question_options`)
2. Delete the question (`competition_question`)
3. Commit transaction

### Key Features

✅ Cascading delete (options first, then question)  
✅ Database transactions  
✅ Rollback on error  
✅ Permanent deletion

⚠️ **Warning:** This operation cannot be undone!

---

## 🔄 Complete Workflow Example

### Scenario: Weekly Competition Setup

#### Step 1: Create Competition Questions

```json
POST /makeAutoGenerateQuestions
{
    "competition_id": 3,
    "questions_count": 14
}
```

**Result:** 14 questions created, 2 per day for 7 days

#### Step 2: Fix a Typo in Question 5

```json
POST /updateComputationQuestions
{
    "id": 5,
    "question_text": "What is the correct spelling of 'programming'?"
}
```

**Result:** Question text updated, options remain unchanged

#### Step 3: Change Options for Question 7

```json
POST /updateComputationQuestions
{
    "id": 7,
    "options": [
        {"option_text": "New Option 1", "is_correct": true},
        {"option_text": "New Option 2", "is_correct": false}
    ]
}
```

**Result:** All old options deleted, new options created

#### Step 4: Remove Duplicate Question 10

```json
POST /deleteComputationQuestions
{
    "id": 10
}
```

**Result:** Question and all its options permanently deleted

---

## 📊 Database Schema

### Tables Involved

#### 1. `competitions`

```sql
id, competition_name, type, start_date, end_date, active
```

#### 2. `competition_question`

```sql
id, competition_id, show_date, question_text, question_type
```

#### 3. `competition_question_options`

```sql
id, question_id, option_text, is_correct
```

#### 4. `questions_bank` (source)

```sql
id, question_text, question_type
```

#### 5. `question_bank_options` (source)

```sql
id, question_id, option_text, is_correct
```

---

## 🔐 Transaction Safety

All UPDATE and DELETE operations use transactions:

```php
DB::beginTransaction();
try {
    // Operations here
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    // Return error
}
```

**Benefits:**

- ✅ Data integrity
- ✅ Atomic operations
- ✅ Automatic rollback on errors
- ✅ Consistent database state

---

## ⚡ Quick Reference

### What Can Be Updated?

| Field          | Create         | Update           | Delete |
| -------------- | -------------- | ---------------- | ------ |
| question_text  | ✅ (from bank) | ✅ Yes           | N/A    |
| question_type  | ✅ (from bank) | ❌ No            | N/A    |
| options        | ✅ (from bank) | ✅ Yes (replace) | N/A    |
| show_date      | ✅ (auto)      | ❌ No            | N/A    |
| competition_id | ✅ (required)  | ❌ No            | N/A    |

### Error Codes

| Code | Meaning          | Example                        |
| ---- | ---------------- | ------------------------------ |
| 200  | Success          | Operation completed            |
| 201  | Created          | Questions generated            |
| 400  | Bad Request      | Invalid question count         |
| 404  | Not Found        | Question/Competition not found |
| 422  | Validation Error | Missing required fields        |
| 500  | Server Error     | Database error                 |

---

## 🎯 Best Practices

### 1. Creating Questions

```javascript
// ✅ Good: Correct count for weekly
{competition_id: 3, questions_count: 14}

// ❌ Bad: Invalid count for weekly
{competition_id: 3, questions_count: 10}
```

### 2. Updating Questions

```javascript
// ✅ Good: Update specific fields
{id: 5, question_text: "New text"}

// ⚠️ Caution: All options will be replaced
{id: 5, options: [...]}
```

### 3. Deleting Questions

```javascript
// ✅ Good: Verify question ID first
// Then delete
{
    id: 5;
}

// ⚠️ Warning: Cannot be undone!
```

---

## 📝 Testing Checklist

### Create (Auto-Generate)

- [ ] Daily competition with custom count
- [ ] Weekly competition with 7 questions
- [ ] Weekly competition with 14 questions
- [ ] Monthly competition with 30 questions
- [ ] Monthly competition with 60 questions
- [ ] Invalid question counts (should fail)
- [ ] Insufficient questions in bank (should fail)

### Update

- [ ] Update question text only
- [ ] Update options only
- [ ] Update both text and options
- [ ] Invalid question ID (should fail)
- [ ] Missing required option fields (should fail)

### Delete

- [ ] Delete existing question
- [ ] Delete non-existent question (should fail)
- [ ] Verify options were deleted
- [ ] Verify transaction rollback on error

---

## 🔗 Related Documentation

- [Auto Generate Questions README](.gemini/auto_generate_questions_README.md)
- [Update API Documentation](.gemini/update_competition_questions_API.md)
- [Delete API Documentation](.gemini/delete_competition_questions_API.md)
- [Date Distribution Bug Fix](.gemini/date_distribution_bugfix.md)

---

## 📞 Support

For issues or questions:

1. Check error messages for specific guidance
2. Review validation requirements
3. Verify database state
4. Check transaction logs

---

## 🆕 Version History

- **v1.3** - Added delete functionality with cascading
- **v1.2** - Added update functionality with transactions
- **v1.1** - Fixed date distribution bug
- **v1.0** - Initial auto-generate implementation

---

**Last Updated:** 2026-01-17  
**Implemented By:** Antigravity AI Assistant
