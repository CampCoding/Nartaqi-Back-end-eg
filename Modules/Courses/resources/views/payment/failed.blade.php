<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فشل الدفع | منصة نرتكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        h1 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: #64748b;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✕</div>
        <h1>عذراً، فشلت عملية الدفع</h1>
        <p>لم نتمكن من إتمام عملية الدفع الخاصة بك. يرجى التأكد من رصيد حسابك أو المحاولة مرة أخرى باستخدام بطاقة أخرى.</p>
        @if(isset($platform) && $platform === 'mobile')
            <p style="font-weight: bold; color: #1e293b;">يمكنك إغلاق هذه الصفحة والعودة إلى التطبيق.</p>
        @else
            <a href="{{ $frontendRedirect }}" class="btn">المحاولة مرة أخرى</a>
        @endif
    </div>
</body>
</html>
