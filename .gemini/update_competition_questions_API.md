# Update Competition Questions API Documentation

## Endpoint: `updateComputationQuestions`

Updates an existing competition question's text and/or options.

## Request Method

`POST`

## Route

```
/api/admin/competitions/updateComputationQuestions
```

## Request Parameters

### Required

- `id` (integer): The ID of the competition question to update
    - Must exist in `competition_question` table

### Optional

- `question_text` (string): New question text
- `options` (array): New options for the question
    - Each option must contain:
        - `option_text` (string, required): The option text
        - `is_correct` (boolean, required): Whether this option is correct

## Request Examples

### Update Question Text Only

```json
POST /api/admin/competitions/updateComputationQuestions
{
    "id": 5,
    "question_text": "ما هي لغة البرمجة الأكثر استخدامًا في تطوير الويب؟"
}
```

### Update Options Only

```json
POST /api/admin/competitions/updateComputationQuestions
{
    "id": 5,
    "options": [
        {
            "option_text": "PHP",
            "is_correct": true
        },
        {
            "option_text": "Python",
            "is_correct": false
        },
        {
            "option_text": "Java",
            "is_correct": false
        },
        {
            "option_text": "Ruby",
            "is_correct": false
        }
    ]
}
```

### Update Both Question Text and Options

```json
POST /api/admin/competitions/updateComputationQuestions
{
    "id": 5,
    "question_text": "ما هي لغة البرمجة الأكثر شعبية؟",
    "options": [
        {
            "option_text": "JavaScript",
            "is_correct": true
        },
        {
            "option_text": "Python",
            "is_correct": false
        },
        {
            "option_text": "Java",
            "is_correct": false
        }
    ]
}
```

## Response

### Success Response (200)

```json
{
    "status": "success",
    "message": "تم تحديث السؤال بنجاح",
    "data": {
        "id": 5,
        "competition_id": 3,
        "show_date": "2026-08-13",
        "question_text": "ما هي لغة البرمجة الأكثر شعبية؟",
        "question_type": "mcq",
        "created_at": "2026-01-17T11:35:00.000000Z",
        "updated_at": "2026-01-17T14:40:00.000000Z",
        "questions": [
            {
                "id": 15,
                "question_id": 5,
                "option_text": "JavaScript",
                "is_correct": true,
                "created_at": "2026-01-17T14:40:00.000000Z",
                "updated_at": "2026-01-17T14:40:00.000000Z"
            },
            {
                "id": 16,
                "question_id": 5,
                "option_text": "Python",
                "is_correct": false,
                "created_at": "2026-01-17T14:40:00.000000Z",
                "updated_at": "2026-01-17T14:40:00.000000Z"
            },
            {
                "id": 17,
                "question_id": 5,
                "option_text": "Java",
                "is_correct": false,
                "created_at": "2026-01-17T14:40:00.000000Z",
                "updated_at": "2026-01-17T14:40:00.000000Z"
            }
        ]
    }
}
```

### Error Responses

#### 404 - Question Not Found

```json
{
    "status": "error",
    "message": "السؤال غير موجود"
}
```

#### 422 - Validation Error

```json
{
    "status": "error",
    "message": "خطأ في البيانات المدخلة",
    "errors": {
        "id": ["The id field is required."],
        "options.0.option_text": [
            "The options.0.option_text field is required when options is present."
        ]
    }
}
```

#### 500 - Server Error

```json
{
    "status": "error",
    "message": "فشل في تحديث السؤال",
    "error": "Error details..."
}
```

## Implementation Details

### Transaction Safety

The method uses database transactions to ensure data integrity:

```php
DB::beginTransaction();
try {
    // Update question and options
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
}
```

### Options Update Logic

When options are provided:

1. **All old options are deleted** from `competition_question_options` table
2. **New options are created** with the provided data
3. **Option IDs change** - this is a complete replacement, not an update

### What Gets Updated

| Field            | Can Update? | Notes                                     |
| ---------------- | ----------- | ----------------------------------------- |
| `question_text`  | ✅ Yes      | Optional, only if provided                |
| `options`        | ✅ Yes      | Optional, completely replaces old options |
| `question_type`  | ❌ No       | Cannot be changed                         |
| `competition_id` | ❌ No       | Cannot be changed                         |
| `show_date`      | ❌ No       | Cannot be changed                         |

## Database Tables Affected

### 1. `competition_question`

- `question_text` may be updated

### 2. `competition_question_options`

- All existing options for the question are **deleted**
- New options are **inserted**

## Comparison with QuestionsBankController

This method follows the same pattern as `updateQuestion` in `QuestionsBankController`:

- ✅ Uses transactions
- ✅ Validates input
- ✅ Deletes old options
- ✅ Creates new options
- ✅ Returns updated question with options
- ✅ Same response format

## Use Cases

### 1. Fix Typo in Question

```json
{
    "id": 10,
    "question_text": "ما هو الفرق بين var و let في JavaScript؟"
}
```

### 2. Change Answer Options

```json
{
    "id": 10,
    "options": [
        { "option_text": "var له block scope", "is_correct": false },
        { "option_text": "let له block scope", "is_correct": true },
        { "option_text": "لا يوجد فرق", "is_correct": false }
    ]
}
```

### 3. Complete Question Revision

```json
{
    "id": 10,
    "question_text": "ما هو الفرق الرئيسي بين var و let؟",
    "options": [
        { "option_text": "Block scoping", "is_correct": true },
        { "option_text": "Hoisting behavior", "is_correct": false },
        { "option_text": "Variable type", "is_correct": false }
    ]
}
```

## Notes

⚠️ **Important Warnings:**

- This operation **cannot be undone** without a database backup
- All old options are permanently deleted when new options are provided
- The `question_type`, `competition_id`, and `show_date` cannot be modified
- Option IDs will change after update (they are recreated)

✅ **Best Practices:**

- Always provide all options when updating (not just the changed ones)
- Ensure at least one option has `is_correct: true`
- Test the update in a development environment first
- Keep backups before bulk updates

## Testing

### Using Postman

1. Set method to `POST`
2. URL: `http://your-domain/api/admin/competitions/updateComputationQuestions`
3. Headers: `Content-Type: application/json`
4. Body: (see examples above)

### Using cURL

```bash
curl -X POST http://your-domain/api/admin/competitions/updateComputationQuestions \
  -H "Content-Type: application/json" \
  -d '{
    "id": 5,
    "question_text": "Updated question text",
    "options": [
      {"option_text": "Option 1", "is_correct": true},
      {"option_text": "Option 2", "is_correct": false}
    ]
  }'
```
