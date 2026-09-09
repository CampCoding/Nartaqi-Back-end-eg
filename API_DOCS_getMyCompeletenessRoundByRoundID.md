# API Documentation: getMyCompeletenessRoundByRoundID

## Endpoint

**POST** `/api/user/rounds/getMyCompeletenessRoundByRoundID`

## Authentication

Required - Bearer Token

## Description

يجيب تفاصيل التدريبات (basic و lecture) التي أكملها الطالب في دورة معينة مع أعلى درجة حصل عليها في كل تدريب.

## Request Parameters

| Parameter | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| round_id  | integer | Yes      | رقم الدورة  |

## Request Example

```json
{
    "round_id": 1
}
```

## Response Success (200)

```json
{
    "status": "success",
    "message": "success",
    "data": {
        "round_id": 1,
        "basic": [
            {
                "content_id": 5,
                "lesson_id": 10,
                "exam_id": 15,
                "exam_name": "اختبار مقدمة في البرمجة",
                "type": "basic",
                "highest_score": "85.50%",
                "created_at": "2024-03-20T10:30:00.000000Z",
                "solved": true
            },
            {
                "content_id": 6,
                "lesson_id": 11,
                "exam_id": 16,
                "exam_name": "اختبار الدوال",
                "type": "basic",
                "highest_score": "0%",
                "created_at": null,
                "solved": false
            }
        ],
        "lecture": [
            {
                "content_id": 8,
                "lesson_id": 12,
                "exam_id": 18,
                "exam_name": "اختبار المتغيرات والثوابت",
                "type": "lecture",
                "highest_score": "92.00%",
                "created_at": "2024-03-22T14:15:00.000000Z",
                "solved": true
            }
        ],
        "full_round": [
            {
                "content_id": null,
                "lesson_id": null,
                "exam_id": 20,
                "exam_name": "الامتحان النصفي",
                "type": "full_round",
                "highest_score": "88.00%",
                "created_at": "2024-04-01T09:00:00.000000Z",
                "solved": true
            }
        ],
        "basic_percentage": "42.75%",
        "lecture_percentage": "92.00%",
        "full_round_percentage": "88.00%",
        "total_percentage": "74.25%",
        "total_completed_basic": 1,
        "total_completed_lecture": 1,
        "total_completed_full_round": 1,
        "total_contents": 11
    }
}
```

## Response Fields Description

| created_at | datetime | تاريخ الحصول على الدرجة (أو null) |
| solved | boolean | هل تم حل الامتحان؟ (true/false) |
| total_completed_basic | integer | عدد تدريبات Basic المكتملة |
| total_completed_lecture | integer | عدد تدريبات Lecture المكتملة |
| total_completed_full_round | integer | عدد امتحانات Full Round المكتملة |
| total_contents | integer | إجمالي عدد التدريبات في الدورة |

## Error Responses

### 400 - Bad Request

```json
{
    "status": "error",
    "message": "Round ID is required",
    "data": null
}
```

### 404 - Not Found

```json
{
    "status": "error",
    "message": "You are not enrolled in this round",
    "data": null
}
```

### 500 - Server Error

```json
{
    "status": "error",
    "message": "Error: [error message]",
    "data": null
}
```

## Notes

- يجب أن يكون الطالب مسجلاً في الدورة ليتمكن من الوصول إليها
- يتم عرض فقط التدريبات التي قام الطالب بإتمامها (لديه درجة فيها)
- النوع type يمكن أن يكون "basic" أو "lecture" فقط
- الدرجة highest_score هي أعلى درجة حصل عليها الطالب في هذا التدريب
