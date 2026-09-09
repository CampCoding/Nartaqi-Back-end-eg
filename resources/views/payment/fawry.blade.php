<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفع فوري</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8 text-center">
        <div class="mb-6 flex justify-center">
            @if(isset($method) && $method == 'Wallet')
                <h2 class="text-xl font-bold text-blue-600">المحفظة الإلكترونية</h2>
            @else
                <img src="https://fawry.com/wp-content/uploads/2021/04/Fawry-Logo-1.png" alt="Fawry" class="h-12 object-contain">
            @endif
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">طلب الدفع</h1>
        <p class="text-gray-600 mb-8">
            @if(isset($method) && $method == 'Wallet')
                يرجى مسح الـ QR Code أو استخدام الرقم المرجعي لإتمام الدفع:
            @else
                يرجى التوجه لأي منفذ دفع (فوري، أمان، بساطة) واستخدام الكود التالي:
            @endif
        </p>

        @if(isset($qrCode) && $qrCode)
        <div class="flex justify-center mb-6 p-4 bg-gray-100 rounded-2xl">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCode) }}" alt="QR Code" class="rounded-lg">
        </div>
        @endif

        <div class="bg-orange-50 border-2 border-dashed border-orange-200 rounded-2xl p-6 mb-8">
            <span class="text-sm text-orange-600 font-bold block mb-2">رقم المرجع / الكود</span>
            <span class="text-4xl font-black text-orange-600 tracking-widest">{{ $fawryCode }}</span>
        </div>

        <div class="text-right space-y-3 mb-8">
            <div class="flex items-center text-gray-700">
                <div class="w-2 h-2 bg-orange-500 rounded-full ml-2"></div>
                <span class="text-sm">هذا الكود صالح حتى: <b>{{ $expireDate }}</b></span>
            </div>
            <div class="flex items-center text-gray-700">
                <div class="w-2 h-2 bg-orange-500 rounded-full ml-2"></div>
                <span class="text-sm">القيمة المطلوب دفعها: <b>100 ج.م</b></span>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <button onclick="window.print()" class="px-8 py-3 bg-gray-800 text-white rounded-xl font-bold hover:bg-gray-900 transition-all">
                طباعة الكود
            </button>
            <a href="/" class="text-gray-500 hover:text-gray-700 font-bold text-sm">العودة للرئيسية</a>
        </div>
    </div>

</body>
</html>
