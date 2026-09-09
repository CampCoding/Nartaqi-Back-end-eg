<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

$con = mysqli_connect("localhost", "campcodi_BusniessMaster", "BusniessMaster1231", "campcodi_BusniessMaster");
if (!$con) die("<div style='text-align:center;padding:50px;color:red;'>فشل الاتصال</div>");
mysqli_set_charset($con, "utf8mb4");

// جلب الأسئلة
$faqs = [];
$q = mysqli_query($con, "SELECT question, answer FROM faqs WHERE language='ar' ORDER BY id DESC");
while ($r = mysqli_fetch_assoc($q)) $faqs[] = $r;

// جلب كل قنوات التواصل
$contacts = [];
$q2 = mysqli_query($con, "SELECT label, value, icon FROM contact_info WHERE language='ar' ORDER BY id ASC");
while ($r2 = mysqli_fetch_assoc($q2)) $contacts[] = $r2;

mysqli_close($con);

// تنسيق رابط الاتصال
function formatContact($label, $value) {
    $value = trim((string)$value);

    if (preg_match('/بريد|إيميل|email/i', $label)) {
        return "<a href='mailto:$value' class='text-blue-600 underline'>$value</a>";
    }

    if (preg_match('/واتساب|هاتف|جوال|phone/i', $label)) {
        $num = preg_replace('/[^0-9+]/', '', $value);
        if (!str_starts_with($num, '+')) $num = '+20' . ltrim($num, '0');
        return "<a href='https://wa.me/$num' class='text-green-600 underline flex items-center gap-2'>$value <span>WhatsApp</span></a>";
    }

    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return "<a href='$value' target='_blank' class='text-blue-600 underline'>$value</a>";
    }

    return nl2br(htmlspecialchars($value));
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المساعدة والدعم</title>

    <!-- Tailwind -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- الخط -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Cairo', sans-serif; background: #f9fafb; }

        /* FAQ */
        .faq-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .faq-question { padding: 20px; font-weight: bold; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .faq-answer { padding: 0 20px 20px; display: none; line-height: 1.8; color: #444; }
        .faq-answer.show { display: block; }

        /* Contact the same design in image */
        .contact-card {
            background: white;
            padding: 20px;
            border-radius: 24px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .contact-card img {
            width: 56px;
            height: 56px;
            object-fit: contain;
        }
    </style>
</head>

<body class="py-6 px-4">

<div class="max-w-4xl mx-auto">

    <h1 class="text-4xl font-bold text-center mb-12 text-gray-800">المساعدة والدعم</h1>

    <!-- الأسئلة الشائعة -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-right">الأسئلة الشائعة</h2>

        <?php if (empty($faqs)): ?>
            <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-500">
                لا توجد أسئلة حالياً
            </div>

        <?php else: ?>
            <?php foreach ($faqs as $f): ?>
                <div class="faq-card">
                    <div class="faq-question" onclick="
                        this.nextElementSibling.classList.toggle('show');
                    ">
                        <span><?= htmlspecialchars($f['question']) ?></span>
                    </div>

                    <div class="faq-answer">
                        <?= nl2br(htmlspecialchars($f['answer'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- قنوات التواصل -->
    <section>
        <h2 class="text-2xl font-bold mb-6 text-right">تواصل معنا</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <?php foreach ($contacts as $c):
                $isImg = filter_var($c['icon'], FILTER_VALIDATE_URL);
            ?>
                <div class="contact-card">
                    
                    <!-- النص -->
                    <div class="flex flex-col text-right">
                        <h3 class="font-bold text-xl mb-1"><?= htmlspecialchars($c['label']) ?></h3>
                        <div class="text-lg text-gray-600">
                            <?= formatContact($c['label'], $c['value']) ?>
                        </div>
                    </div>

                    <!-- الأيقونة -->
                    <div>
                        <?php if ($isImg): ?>
                            <img src="<?= $c['icon'] ?>" alt="icon">
                        <?php else: ?>
                            <div class="text-5xl"><?= $c['icon'] ?></div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>

</body>
</html>
