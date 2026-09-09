<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختر وسيلة الدفع</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    
    <div class="max-w-2xl w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">إتمام الاشتراك</h1>
            <p class="text-gray-600 mt-2">أنت بصدد الاشتراك في: <span class="text-orange-600 font-bold">{{ $round->name }}</span></p>
            <div class="mt-4 bg-orange-100 text-orange-700 py-2 px-6 rounded-full inline-block font-bold">
                المبلغ المطلوب: {{ $round->price }} ج.م
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($methods as $method)
            <a href="{{ route('payment.initiate', ['round_id' => $round_id, 'student_id' => $student_id, 'method_id' => $method['paymentId']]) }}" 
               class="bg-white p-6 rounded-2xl shadow-md border-2 border-transparent hover:border-orange-500 transition-all flex flex-col items-center group">
                <img src="{{ $method['logo'] }}" alt="{{ $method['name_ar'] }}" class="h-16 object-contain mb-4 grayscale group-hover:grayscale-0 transition-all">
                <span class="font-bold text-gray-800 text-lg">{{ $method['name_ar'] }}</span>
                <span class="text-xs text-gray-400 mt-1">دفع آمن عبر فواتيرك</span>
            </a>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="/" class="text-gray-500 hover:text-gray-700 font-bold">إلغاء والعودة</a>
        </div>
    </div>

</body>
</html>
