<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') | نرتقي</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: #f4f7f6;
            color: #1f2d2a;
            font-family: 'Tajawal', 'Cairo', Tahoma, Arial, sans-serif;
            text-align: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 20px;
            padding: 40px 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }
        .icon { font-size: 52px; line-height: 1; margin-bottom: 16px; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { font-size: 16px; line-height: 1.8; margin: 0; color: #55625f; }
        .brand { margin-top: 28px; font-size: 14px; color: #8a9693; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">@yield('icon')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="brand">منصة نرتقي للتدريب وتنمية المهارات</div>
    </div>
</body>
</html>
