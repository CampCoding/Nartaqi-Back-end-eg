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

                $smsMessage = "أهلاً بك يا $studentName\nتهانينا! لقد تم اشتراكك بنجاح في دورة: $roundName\nنتمنى لك رحلة تعليمية ممتعة ومفيدة مع منصة نرتقي.";

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
}
