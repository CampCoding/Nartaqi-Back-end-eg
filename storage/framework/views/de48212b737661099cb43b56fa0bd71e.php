<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تمت عملية الدفع بنجاح</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    
    <!-- قسم المعالجة -->
    <div id="processing-section" class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-700 text-lg font-bold">جاري تأكيد الدفع وتفعيل الدورة...</p>
    </div>

    <!-- قسم النجاح (مخفي افتراضياً) -->
    <div id="success-section" class="max-w-xl w-full bg-white rounded-3xl shadow-2xl p-8 text-center hidden">
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
        </div>
        
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">تمت عملية الدفع بنجاح!</h1>
        <p class="text-gray-600 mb-8 text-lg">شكراً لك، لقد تم تأكيد الدفع وتفعيل الدورة في حسابك. يمكنك الآن البدء في رحلة التعلم.</p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a id="course-link" href="/" class="px-8 py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-orange-200">
                ابدأ الدورة الآن
            </a>
            <a href="/" class="px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold transition-all">
                العودة للرئيسية
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enrollStudent = async () => {
                try {
                    const pendingPayment = JSON.parse(localStorage.getItem("pending_payment") || "{}");

                    if (!pendingPayment.roundId || !pendingPayment.studentId || !pendingPayment.token) {
                        const savedId = localStorage.getItem("last_enrolled_course");
                        showSuccess(savedId);
                        return;
                    }

                    const roundId = pendingPayment.roundId;
                    localStorage.setItem("last_enrolled_course", roundId);
                    localStorage.removeItem("pending_payment");

                    await axios.post(`${window.location.origin}/api/user/rounds/enrollInCourse`, {
                        round_id: roundId,
                        student_id: pendingPayment.studentId,
                    }, {
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${pendingPayment.token}`
                        }
                    });

                    showSuccess(roundId);
                } catch (error) {
                    console.error("Enrollment Error:", error);
                    showSuccess(localStorage.getItem("last_enrolled_course"));
                }
            };

            function showSuccess(courseId) {
                document.getElementById('processing-section').classList.add('hidden');
                document.getElementById('success-section').classList.remove('hidden');
                if (courseId) {
                    document.getElementById('course-link').href = `/course/${courseId}`;
                }
            }

            enrollStudent();
        });
    </script>
</body>
</html><?php /**PATH D:\campCoding\Nartaqi-Back-end\resources\views\payment\success.blade.php ENDPATH**/ ?>