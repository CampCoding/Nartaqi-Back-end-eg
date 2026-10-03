<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Models\Student;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\TeachersModel;
use Modules\Courses\Transformers\RoundsResource;
use Modules\Courses\Transformers\StudentsResource;
use Modules\Courses\Transformers\StudentResource;
use Modules\Courses\Http\Requests\SendBulkWhatsappMessageRequest;

// Include the SMS file from Authentication module
require_once base_path('Modules/Authentication/smsfile.php');

class StudentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('courses::index');
    }

    public function get_all_students(Request $request)
    {
        $perPage = (int) $request->get('per_page', 5);
        $students = Student::paginate(10000);
        return new StudentsResource($students);
    }

    public function get_student_by_phone(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:255',
        ]);
        $student = Student::where('phone', $data['phone'])->first();
        return new StudentResource($student);
    }
    public function get_student_rounds(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);
        $perPage = (int) $request->get('per_page', 5);

        // Get round IDs from the pivot table student_rounds (UserRounds model)
        $roundIds = UserRounds::where('student_id', $data['student_id'])
            ->pluck('round_id');

        // Paginate the enrolled rounds
        $rounds = Rounds::whereIn('id', $roundIds)->paginate($perPage);

        // Reduce each round to only id and name for this endpoint
        $collection = $rounds->getCollection()->map(function ($round) {
            return [
                'id' => $round->id,
                'name' => $round->name,
                'image' => $round->image_url,
            ];
        });

        $rounds->setCollection($collection);

        return new RoundsResource($rounds);
    }

    public function get_student_round_contents(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);
        $rounds = Rounds::where('student_id', $data['student_id'])->get();
        $roundContents = RoundContetModel::whereIn('round_id', $rounds->pluck('id'))->get();
        return new RoundsResource($roundContents);
    }
    public function inroll_in_round(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
        ]);
        $studentRound = UserRounds::create([
            'student_id' => $data['student_id'],
            'round_id' => $data['round_id'],
            'status' => 'active',
            'end_date' => now()->addYears(1),
            'day' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'payment_id' => null,
        ]);

        // Send congratulatory SMS
        try {
            $student = Student::find($data['student_id']);
            $round = Rounds::find($data['round_id']);

            if ($student && $round) {
                $studentName = $student->name;
                $roundName = $round->name;
                $phone = $student->phone;

                // Fix phone format
                if (strpos($phone, '0') === 0) {
                    $phone = '2' . $phone;
                }

                $smsMessage = "تم تفعيل اشتراكك في دورة: $roundName .. لمعرفة كيفية متابعة الدورة من خلال المنصة شاهد الفيديو التعريفي https://www.youtube.com/watch?v=dqwkvk7JU_I";

                sendWawpMessage($phone, $smsMessage);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Inroll Enrollment SMS failed: ' . $e->getMessage());
        }

        return res_data($studentRound, 'success', 200);
    }
    public function cancel_inroll_from_round(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
        ]);
        $studentRound = UserRounds::where('student_id', $data['student_id'])->where('round_id', $data['round_id'])->first();
        $studentRound->delete();
        return res_data($studentRound, 'success', 200);
    }



    public function get_all_active_rounds(Request $request)
    {
        $rounds = Rounds::where('source', '0')
            ->select('id', 'name', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return res_data($rounds, 'success', 200);
    }

    public function sendBulkWhatsappMessage(SendBulkWhatsappMessageRequest $request)
    {
        // Sending to many students sequentially easily exceeds the default 30s limit;
        // stopping midway would leave the batch half-sent.
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        $data = $request->validated();

        $fileUrl = null;
        if ($request->hasFile('file')) {
            try {
                $destinationPath = public_path('storage/broadcast_messages');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('file');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $fileName);
                $fileUrl = asset('storage/broadcast_messages/' . $fileName);
            } catch (\Throwable $e) {
                return res_data('فشل رفع الملف: ' . $e->getMessage(), 'error', 500);
            }
        }

        $students = Student::whereIn('id', $data['student_ids'])->get(['id', 'name', 'phone']);

        $results = [];
        foreach ($students as $student) {
            $personalizedMessage = str_contains($data['message'], '{name}')
                ? str_replace('{name}', $student->name, $data['message'])
                : "أ. {$student->name}\n" . $data['message'];

            $deliveredAs = $fileUrl ? 'file' : 'text';
            $mediaError = null;
            try {
                if ($fileUrl) {
                    $result = sendWawpPdf($student->phone, $fileUrl, '', $personalizedMessage);
                    // If the gateway rejects the media, still deliver the file as a link.
                    if (($result['status'] ?? 'error') !== 'success') {
                        $mediaError = $result['error'] ?? null;
                        $deliveredAs = 'link';
                        $result = sendWawpMessage($student->phone, $personalizedMessage . "\n\n📎 " . $fileUrl);
                    }
                } else {
                    $result = sendWawpMessage($student->phone, $personalizedMessage);
                }
            } catch (\Throwable $e) {
                $result = ['status' => 'error', 'error' => $e->getMessage()];
            }

            $results[] = [
                'student_id' => $student->id,
                'phone' => $student->phone,
                'status' => $result['status'] ?? 'error',
                'delivered_as' => $deliveredAs,
                'media_error' => $mediaError,
                'error' => $result['error'] ?? null,
            ];
        }

        $successCount = collect($results)->where('status', 'success')->count();

        return res_data([
            'total' => count($results),
            'success_count' => $successCount,
            'failed_count' => count($results) - $successCount,
            'file_url' => $fileUrl,
            'details' => $results,
        ], 'success', 200);
    }
}
