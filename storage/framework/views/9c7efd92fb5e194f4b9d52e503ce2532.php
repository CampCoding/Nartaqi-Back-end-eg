<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم الدفع بنجاح | منصة نرتقي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --bg: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Ambient background glow */
        .glow {
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            z-index: -1;
            border-radius: 50%;
        }

        .card {
            background: white;
            padding: 45px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
            text-align: center;
            max-width: 420px;
            width: 90%;
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            transform: translateY(20px);
            opacity: 0;
            animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Dynamic Circle Countdown container */
        .countdown-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .countdown-svg {
            transform: rotate(-90deg);
            width: 100px;
            height: 100px;
        }

        .countdown-bg {
            fill: none;
            stroke: #f1f5f9;
            stroke-width: 6;
        }

        .countdown-bar {
            fill: none;
            stroke: var(--success);
            stroke-width: 6;
            stroke-linecap: round;
            stroke-dasharray: 283; /* 2 * PI * r (r=45) */
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 1s linear;
        }

        .countdown-text {
            position: absolute;
            font-size: 26px;
            font-weight: 700;
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-success {
            font-size: 32px;
            animation: scaleCheck 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes scaleCheck {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        h1 {
            color: var(--text-dark);
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            padding: 14px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Tiny helper text below the button */
        .redirect-notice {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 15px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="glow"></div>
    <div class="card">
        <!-- SVG Circular Countdown -->
        <div class="countdown-container">
            <svg class="countdown-svg" viewBox="0 0 100 100">
                <circle class="countdown-bg" cx="50" cy="50" r="45" />
                <circle id="progress-bar" class="countdown-bar" cx="50" cy="50" r="45" />
            </svg>
            <div id="countdown-number" class="countdown-text">10</div>
        </div>

        <h1>تمت عملية الدفع بنجاح!</h1>
        <p>شكراً لك، تم استلام مبلغ الاشتراك وتفعيل الدورة في حسابك بنجاح. يمكنك الآن البدء في التعلم فوراً.</p>
        
        <?php if(isset($platform) && $platform === 'mobile'): ?>
            <p style="margin-top: 10px; font-weight: bold; color: var(--primary);">يمكنك إغلاق هذه الصفحة والعودة إلى التطبيق الآن.</p>
        <?php else: ?>
            <a href="<?php echo e($frontendRedirect ?? 'https://nartaqi-user.vercel.app'); ?>" class="btn">
                الانتقال للمنصة في الحال
            </a>
            
            <span class="redirect-notice">سيتم توجيهك تلقائياً خلال <strong id="seconds-label">10</strong> ثوانٍ...</span>
        <?php endif; ?>
    </div>

    <script>
        <?php if(!isset($platform) || $platform !== 'mobile'): ?>
        const redirectUrl = "<?php echo e($frontendRedirect ?? 'https://nartaqi-user.vercel.app'); ?>";
        let timeLeft = 10;
        
        const countdownNumberEl = document.getElementById('countdown-number');
        const secondsLabelEl = document.getElementById('seconds-label');
        const progressBar = document.getElementById('progress-bar');
        
        const totalLength = 283; // Circumference of circle
        
        const timer = setInterval(() => {
            timeLeft--;
            
            if (timeLeft >= 0) {
                // Update text
                countdownNumberEl.textContent = timeLeft;
                secondsLabelEl.textContent = timeLeft;
                
                // Update SVG circular progress offset
                const offset = totalLength - (timeLeft / 10) * totalLength;
                progressBar.style.strokeDashoffset = offset;
            }
            
            if (timeLeft === 0) {
                // Change number to checkmark icon
                countdownNumberEl.innerHTML = '<span class="icon-success">✓</span>';
            }
            
            if (timeLeft < 0) {
                clearInterval(timer);
                window.location.href = redirectUrl;
            }
        }, 1000);
        <?php else: ?>
        // Mobile behavior: show checkmark immediately and no countdown
        const countdownNumberEl = document.getElementById('countdown-number');
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.strokeDashoffset = 0;
        countdownNumberEl.innerHTML = '<span class="icon-success">✓</span>';
        <?php endif; ?>
    </script>
</body>

</html><?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Courses\resources\views\payment\success.blade.php ENDPATH**/ ?>