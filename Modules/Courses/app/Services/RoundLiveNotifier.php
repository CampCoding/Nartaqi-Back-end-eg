<?php

namespace Modules\Courses\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Models\Student;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\RoundLiveModel;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\UserRounds;

require_once base_path('Modules/Authentication/smsfile.php');

/**
 * WhatsApp notices to a round's students about a live session: "be ready" when it's created
 * without a link, and the full join details once it has one.
 */
class RoundLiveNotifier
{
    /**
     * Sends after the HTTP response is flushed, so saving the live session isn't kept waiting
     * on one WhatsApp call per student.
     */
    public function notifyAfterResponse(RoundLiveModel $live, bool $linkJustAdded = false): void
    {
        $liveId = $live->id;

        app()->terminating(function () use ($liveId, $linkJustAdded) {
            if (function_exists('set_time_limit')) {
                set_time_limit(0);
            }
            if (function_exists('ignore_user_abort')) {
                ignore_user_abort(true);
            }

            try {
                $live = RoundLiveModel::find($liveId);
                if ($live) {
                    $this->notify($live, $linkJustAdded);
                }
            } catch (\Throwable $e) {
                Log::error('Round live WhatsApp notice failed', ['live_id' => $liveId, 'error' => $e->getMessage()]);
            }
        });
    }

    public function notify(RoundLiveModel $live, bool $linkJustAdded = false): void
    {
        if (blank($live->date) || blank($live->time) || $this->isPast($live->date)) {
            return;
        }

        $roundContentId = LessonsModel::whereKey($live->lesson_id)->value('round_content_id');
        $roundId = $roundContentId ? RoundContetModel::whereKey($roundContentId)->value('round_id') : null;
        if (!$roundId) {
            return;
        }
        $roundName = (string) Rounds::whereKey($roundId)->value('name');

        $students = Student::whereIn(
            'id',
            UserRounds::where('round_id', $roundId)->where('status', 'active')->select('student_id')
        )->get(['id', 'name', 'phone']);

        foreach ($students as $student) {
            try {
                $result = sendWawpMessage(
                    (string) $student->phone,
                    $this->message($live, $roundName, (string) $student->name, $linkJustAdded)
                );
                if (($result['status'] ?? 'error') !== 'success') {
                    Log::warning('Round live WhatsApp notice not delivered', [
                        'live_id' => $live->id,
                        'student_id' => $student->id,
                        'error' => $result['error'] ?? $result['message'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Round live WhatsApp notice failed', [
                    'live_id' => $live->id,
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function message(RoundLiveModel $live, string $roundName, string $studentName, bool $linkJustAdded = false, ?Carbon $now = null): string
    {
        $now = $now ?? now();
        $greeting = (int) $now->format('H') < 12 ? 'صباح الخير' : 'مساء الخير';
        $when = $this->dayPhrase($live->date, $now);
        $time = $this->timePhrase($live->time, $live->end_time);
        $session = $roundName !== '' ? "«{$roundName} - {$live->title}»" : "«{$live->title}»";

        if (blank($live->link)) {
            return implode("\n", [
                "{$greeting} أ. {$studentName}👋✨",
                "نود إعلامك بموعد البث المباشر لـ {$session}.",
                "⏰ الموعد: {$when} الساعة {$time}",
                'كن مستعدًا 💪، وسيصلك رابط الدخول فور تجهيز البث، كما ستجده في تبويب "البثوث المباشرة" من حسابك بالمنصة.',
                'نلقاك على خير.. ودمت متميزًا 🌟',
                'منصة نرتقي',
            ]);
        }

        $lines = ["{$greeting} أ. {$studentName}👋✨"];
        if ($linkJustAdded) {
            $lines[] = '✅ رابط البث المباشر أصبح جاهزًا.';
        }
        $lines[] = "جاهز للقائنا {$when}؟ منتظرينك في البث المباشر الممتع لـ {$session}.";
        $lines[] = "⏰ الموعد: {$when} الساعة {$time}";
        $lines[] = "🔗 رابط الدخول المباشر: ({$live->link})";
        if (filled($live->meeting_id)) {
            $lines[] = "🆔 رقم الاجتماع (Meeting ID): {$live->meeting_id}";
        }
        if (filled($live->password)) {
            $lines[] = "🔑 رمز الدخول (Passcode): {$live->password}";
        }
        $lines[] = '(أو يمكنك الدخول بسهولة عبر تبويب "البثوث المباشرة" من حسابك بالمنصة).';
        $lines[] = 'نلقاك على خير.. ودمت متميزًا 🌟';
        $lines[] = 'منصة نرتقي';

        return implode("\n", $lines);
    }

    private function isPast($date): bool
    {
        try {
            return Carbon::parse($date)->startOfDay()->lt(now()->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    private function dayPhrase($date, Carbon $now): string
    {
        try {
            $day = Carbon::parse($date, $now->getTimezone())->startOfDay();
        } catch (\Throwable) {
            return (string) $date;
        }

        $today = $now->copy()->startOfDay();
        if ($day->equalTo($today)) {
            return 'اليوم';
        }
        if ($day->equalTo($today->copy()->addDay())) {
            return 'غدًا';
        }

        return 'يوم ' . $day->locale('ar')->translatedFormat('l j F');
    }

    // Stored as "hh:mm AM/PM" by the admin panel; anything unparseable is shown as entered.
    private function timePhrase($start, $end): string
    {
        $from = $this->clock($start);

        return blank($end) ? $from : "{$from} حتى {$this->clock($end)}";
    }

    private function clock($value): string
    {
        try {
            $time = Carbon::parse(trim((string) $value));
        } catch (\Throwable) {
            return (string) $value;
        }

        return $time->format('h:i') . ' ' . ($time->format('A') === 'AM' ? 'صباحًا' : 'مساءً');
    }
}
