# Delete Competition Question API Documentation

## Endpoint: `deleteComputationQuestions`

Deletes a competition question and all its associated options.

## Request Method

`POST`

## Route

```
/api/admin/competitions/deleteComputationQuestions
```

## Request Parameters

### Required

- `id` (integer): The ID of the competition question to delete
    - Must exist in `competition_question` table

## Request Example

```json
POST /api/admin/competitions/deleteComputationQuestions
{
    "id": 5
}
```

## Response

### Success Response (200)

```json
{
    "status": "success",
    "message": "تم حذف السؤال بنجاح"
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
        "id": ["The id field is required."]
    }
}
```

#### 500 - Server Error

```json
{
    "status": "error",
    "message": "فشل في حذف السؤال",
    "error": "Error details..."
}
```

## Implementation Details

### Deletion Order

The method follows this sequence:

1. **Validate** the question ID exists
2. **Find** the question in the database
3. **Delete all options** associated with this question
4. **Delete the question** itself
5. **Commit** the transaction

```php
// Delete all options first
CompetitionQuestionsOptionsModel::where('question_id', $id)->delete();

// Then delete the question
$question->delete();
```

### Transaction Safety

The method uses database transactions to ensure data integrity:

```php
DB::beginTransaction();
try {
    // Delete options and question
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();  // Rollback if any error occurs
}
```

### Cascading Deletion

When a question is deleted:

- ✅ All its **options** are deleted first
- ✅ The **question** itself is then deleted
- ⚠️ This operation is **permanent** and cannot be undone

## Database Tables Affected

### 1. `competition_question_options`

- All options where `question_id` matches the deleted question are **deleted**

### 2. `competition_question`

- The question record is **deleted**

## What Gets Deleted

| Item                 | Deleted? | Notes                                     |
| -------------------- | -------- | ----------------------------------------- |
| Question record      | ✅ Yes   | Permanently removed                       |
| All question options | ✅ Yes   | All options for this question             |
| Competition          | ❌ No    | Competition remains intact                |
| Other questions      | ❌ No    | Other questions in the competition remain |

## Comparison with Other Delete Methods

| Feature           | `deleteComputationQuestions` | `deleteQuestion` (Bank) |
| ----------------- | ---------------------------- | ----------------------- |
| Deletes Question  | ✅ Yes                       | ✅ Yes                  |
| Deletes Options   | ✅ Yes                       | ✅ Yes                  |
| Uses Transactions | ✅ Yes                       | ✅ Yes                  |
| Rollback on Error | ✅ Yes                       | ✅ Yes                  |
| Table             | `competition_question`       | `questions_bank`        |

## Use Cases

### 1. Remove Wrong Question

If a question was added by mistake:

```json
{
    "id": 15
}
```

### 2. Clean Up After Competition

After a competition ends, remove old questions:

```json
{
    "id": 25
}
```

### 3. Fix Duplicate Questions

If the same question was added twice:

```json
{
    "id": 30
}
```

## Safety Considerations

⚠️ **Warnings:**

- This operation is **irreversible** without a database backup
- All options for this question will be permanently deleted
- Students who answered this question will lose their answers (if stored)
- Consider archiving instead of deleting for audit purposes

✅ **Best Practices:**

- Always verify the question ID before deletion
- Consider showing the question details to the user for confirmation
- Keep database backups
- Log deletion actions for audit trail
- Consider soft deletes if you need to restore data later

## Testing

### Using Postman

1. Set method to `POST`
2. URL: `http://your-domain/api/admin/competitions/deleteComputationQuestions`
3. Headers: `Content-Type: application/json`
4. Body:

```json
{
    "id": 5
}
```

### Using cURL

```bash
curl -X POST http://your-domain/api/admin/competitions/deleteComputationQuestions \
  -H "Content-Type: application/json" \
  -d '{"id": 5}'
```

## Example Workflow

### Before Deletion

```
competition_question (id: 5)
├── question_text: "What is PHP?"
├── competition_id: 3
├── show_date: 2026-08-13
└── Options:
    ├── Option 1: "A programming language" (correct)
    ├── Option 2: "A database"
    ├── Option 3: "An operating system"
    └── Option 4: "A framework"
```

### After Deletion

```
All data removed:
- Question ID 5: DELETED ✓
- 4 Options: DELETED ✓
- Competition ID 3: INTACT ✓
```

## SQL Equivalent

The method performs operations equivalent to:

```sql
-- Step 1: Delete options
DELETE FROM competition_question_options WHERE question_id = 5;

-- Step 2: Delete question
DELETE FROM competition_question WHERE id = 5;
```

## Related Endpoints

- `makeAutoGenerateQuestions` - Create questions from question bank
- `updateComputationQuestions` - Update existing question
- `getAllCompetitions` - List all competitions with their questions

## Notes

- The method name uses "Computation" but it's actually for "Competition" questions
- Consider renaming to `deleteCompetitionQuestion` for consistency
- The deletion happens within a transaction for data integrity
- All database operations are rolled back if any step fails

## Error Handling

The method handles three types of errors:

1. **Not Found (404)**: Question ID doesn't exist
2. **Validation (422)**: Invalid input data
3. **General (500)**: Database or other errors

All errors trigger a transaction rollback to maintain database consistency.
