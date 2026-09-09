<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فشلت عملية الدفع</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-red-50 flex items-center justify-center min-h-screen p-4">
    
    <div class="max-w-xl w-full bg-white rounded-3xl shadow-2xl p-8 text-center animate-[pulse_2s_infinite]">
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
        </div>
        
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">فشلت عملية الدفع!</h1>
        <p class="text-gray-600 mb-8 text-lg">للأسف، لم نتمكن من إتمام عملية الدفع حالياً. يرجى التأكد من رصيد حسابك أو بيانات البطاقة والمحاولة مرة أخرى.</p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/" class="px-8 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-red-200">
                المحاولة مرة أخرى
            </a>
            <a href="/" class="px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold transition-all">
                العودة للرئيسية
            </a>
        </div>
        
        <div class="mt-8 pt-6 border-t border-gray-100">
            <p class="text-sm text-gray-500">إذا استمرت المشكلة، يمكنك التواصل مع الدعم الفني لمساعدتك.</p>
        </div>
    </div>

</body>
</html>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\resources\views\payment\failed.blade.php ENDPATH**/ ?>