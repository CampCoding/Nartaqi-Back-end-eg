<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Http\Requests\AddExamPdfRequest;
use Modules\Courses\Http\Requests\AddExamVideoRequest;
use Modules\Courses\Http\Requests\EditExamPdfRequest;
use Modules\Courses\Http\Requests\EditExamVideoRequest;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundExamSectionModel;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Services\ExamCopyService;
use Illuminate\Support\Facades\DB;

class AssignExamController extends Controller
{
    public function __construct(private ExamCopyService $examCopyService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function add_exam_video(AddExamVideoRequest $request)
    {
        $data = $request->validated();
        // Not sent (or empty) = locked, the column default.
        if (!isset($data['is_locked'])) {
            unset($data['is_locked']);
        }
        $exam_video = ExamVideoModel::create($data);
        return res_data($exam_video->fresh(), 'success', 200);
    }
    public function edit_exam_video(EditExamVideoRequest $request)
    {
        $data = $request->validated();
        // The lock only changes when it is sent.
        if (!isset($data['is_locked'])) {
            unset($data['is_locked']);
        }
        $exam_video = ExamVideoModel::find($data['id']);
        $exam_video->update($data);
        return res_data($exam_video, 'success', 200);
    }

    /**
     * Lock or open a training/exam video for students. ids lets the admin change several at once.
     */
    public function setExamVideoLock(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:exam_videos,id',
            'is_locked' => 'required|boolean',
        ]);

        ExamVideoModel::whereIn('id', $data['ids'])->update(['is_locked' => (bool) $data['is_locked']]);

        return res_data(
            ExamVideoModel::whereIn('id', $data['ids'])->get(),
            $data['is_locked'] ? 'تم قفل الفيديو' : 'تم فتح الفيديو',
            200
        );
    }
    public function delete_exam_video(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_videos,id',
        ]);
        $exam_video = ExamVideoModel::find($data['id']);
        $exam_video->delete();
        return res_data('success', 'success', 200);
    }


    public function add_exam_pdf(AddExamPdfRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('pdf_url')) {
            $data['pdf_url'] = $this->storeExamPdf($request->file('pdf_url'));
        }

        $exam_pdf = ExamPdfsModel::create($data);
        return res_data($exam_pdf, 'success', 200);
    }

    public function edit_exam_pdf(EditExamPdfRequest $request)
    {
        $data = $request->validated();
        $exam_pdf = ExamPdfsModel::findOrFail($data['id']);

        if ($request->hasFile('pdf_url')) {
            $newPath = $this->storeExamPdf($request->file('pdf_url'));

            if ($exam_pdf->pdf_url && Storage::disk('public')->exists($exam_pdf->pdf_url)) {
                Storage::disk('public')->delete($exam_pdf->pdf_url);
            }

            $data['pdf_url'] = $newPath;
        }

        $exam_pdf->update($data);
        return res_data($exam_pdf->fresh(), 'success', 200);
    }

    public function delete_exam_pdf(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_pdfs,id',
        ]);
        $exam_pdf = ExamPdfsModel::find($data['id']);
        $exam_pdf->delete();
        if ($exam_pdf->pdf_url && Storage::disk('public')->exists($exam_pdf->pdf_url)) {
            Storage::disk('public')->delete($exam_pdf->pdf_url);
        }
        return res_data('success', 'success', 200);
    }

    /**
     * Store the uploaded exam PDF and return the relative storage path.
     */
    private function storeExamPdf($file): string
    {
        return $file->store('exam_pdfs', 'public') ?: Uploads::storageFailed($file, 'exam_pdfs');
    }






    public function assign_intern_exam_round(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'lesson_or_round_id' => 'required',
            'round_exam_section_id' => 'nullable|integer',
        ]);

        $sectionError = RoundExamSectionModel::assignmentError($data['round_exam_section_id'] ?? null, $data['type'], $data['lesson_or_round_id']);
        if ($sectionError) {
            return res_data($sectionError, 'خطأ في التحقق من البيانات', 422);
        }

        // A bank exam is never attached to a round/lesson directly: the round/lesson gets its own copy,
        // exactly like assign_exam_round. Otherwise "delete" from the round (delete_exam) deletes the
        // bank exam itself, and a lesson-attached bank exam vanishes from getAllExams.
        $isBankExam = AdminExamModel::where('id', $data['exam_id'])->value('from_copy') != '1';

        if ($isBankExam) {
            return $this->assignCopy($data);
        }

        // The exam is already a copy: attach it as it is. The same exam is never assigned twice to the
        // same round/lesson: adding it again returns the existing assignment (moved to the given section,
        // if one was sent).
        $existing = $this->findAssignment($data, [(int) $data['exam_id']]);

        if (!$existing && !$this->claimAssign($data)) {
            // A second click while the first request is still being saved.
            $existing = $this->findAssignment($data, [(int) $data['exam_id']]);
        }

        if ($existing) {
            return $this->alreadyAssigned($existing, $data);
        }

        $assign_exam_round = AssignExamModel::create([
            'type' => $data['type'],
            'exam_id' => $data['exam_id'],
            'lesson_or_round_id' => $data['lesson_or_round_id'],
            'round_exam_section_id' => $data['round_exam_section_id'] ?? null,
        ]);

        return res_data($assign_exam_round, 'تم تعيين الامتحان بنجاح', 200);
    }

    /**
     * Copy a bank exam and attach the copy. If this round/lesson already has the bank exam (attached
     * directly, the old way) or a copy of it, nothing is copied and the existing assignment is returned.
     */
    private function assignCopy(array $data)
    {
        $sourceId = (int) $data['exam_id'];
        if (AdminExamModel::tracksCopySource()) {
            $sourceId = (int) (AdminExamModel::where('id', $sourceId)->value('copied_from_id') ?: $sourceId);
        }

        $existing = $this->findAssignment($data, AdminExamModel::familyIds($sourceId));
        if ($existing) {
            return $this->alreadyAssigned($existing, $data);
        }

        if (!$this->claimAssign($data)) {
            return res_data('الامتحان بيتضاف دلوقتي، استنى ثواني', 'duplicate request', 409);
        }

        // Copy the exam (sections, paragraphs, questions, options, answers) and assign the copy,
        // all or nothing, so a failure can't leave a half-copied exam behind.
        $assign_exam_round = DB::transaction(function () use ($data) {
            $examIdMap = [];
            $newExam = $this->examCopyService->copyExamWithQuestions((int) $data['exam_id'], $examIdMap);

            $maxSort = AssignExamModel::where('lesson_or_round_id', $data['lesson_or_round_id'])
                ->where('type', $data['type'])
                ->max('sort_number');

            return AssignExamModel::create([
                'type' => $data['type'],
                'exam_id' => $newExam->id,
                'lesson_or_round_id' => $data['lesson_or_round_id'],
                'show_date' => $data['show_date'] ?? null,
                'sort_number' => $maxSort ? $maxSort + 1 : 1,
                'round_exam_section_id' => $data['round_exam_section_id'] ?? null,
            ]);
        });

        return res_data($assign_exam_round, 'تم نسخ الامتحان و تعيينه بنجاح', 200);
    }

    private function findAssignment(array $data, array $examIds): ?AssignExamModel
    {
        return AssignExamModel::where('type', $data['type'])
            ->where('lesson_or_round_id', $data['lesson_or_round_id'])
            ->whereIn('exam_id', $examIds)
            ->orderBy('id')
            ->first();
    }

    private function alreadyAssigned(AssignExamModel $existing, array $data)
    {
        if (!empty($data['round_exam_section_id']) && (int) $existing->round_exam_section_id !== (int) $data['round_exam_section_id']) {
            $existing->update(['round_exam_section_id' => $data['round_exam_section_id']]);
        }

        return res_data($existing, 'الامتحان مضاف بالفعل', 200);
    }

    /**
     * Blocks the same exam from being assigned to the same round/lesson twice within a few seconds
     * (a double click on "add"). False means another identical request just got there first.
     */
    private function claimAssign(array $data): bool
    {
        return Cache::store('file')->add(
            'assign-exam:' . $data['type'] . ':' . $data['exam_id'] . ':' . $data['lesson_or_round_id'],
            true,
            10
        );
    }

    /**
     * Exams assigned more than once to the same round or lesson. Without apply=1 it only lists them;
     * with apply=1 it keeps one assignment per exam (the one with a show_date, otherwise the last one,
     * same row the listings show) and deletes the rest. Students' scores are kept on the exam itself,
     * so nothing a student did is lost.
     */
    public function removeDuplicateExamAssignments(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'nullable|integer',
            'apply' => 'nullable|boolean',
        ]);

        $apply = (bool) ($data['apply'] ?? false);

        $duplicateKeys = AssignExamModel::query()
            ->select('type', 'exam_id', 'lesson_or_round_id')
            ->when(!empty($data['round_id']), function ($q) use ($data) {
                $lessonIds = LessonsModel::whereIn('round_content_id', DB::table('round_contents')->where('round_id', $data['round_id'])->select('id'))->pluck('id');
                $q->where(fn ($w) => $w->where(fn ($r) => $r->where('type', 'full_round')->where('lesson_or_round_id', $data['round_id']))
                    ->orWhere(fn ($l) => $l->where('type', 'lesson')->whereIn('lesson_or_round_id', $lessonIds)));
            })
            ->groupBy('type', 'exam_id', 'lesson_or_round_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $groups = [];
        $removedCount = 0;

        DB::transaction(function () use ($duplicateKeys, $apply, &$groups, &$removedCount) {
            foreach ($duplicateKeys as $key) {
                $rows = AssignExamModel::where('type', $key->type)
                    ->where('exam_id', $key->exam_id)
                    ->where('lesson_or_round_id', $key->lesson_or_round_id)
                    ->get();

                $keep = AssignExamModel::keeperFor($rows);
                $remove = $rows->where('id', '!=', $keep->id)->values();

                if ($apply) {
                    // Don't lose a section the kept row doesn't have.
                    $section = $remove->pluck('round_exam_section_id')->filter()->first();
                    if (!$keep->round_exam_section_id && $section) {
                        $keep->update(['round_exam_section_id' => $section]);
                    }
                    AssignExamModel::whereIn('id', $remove->pluck('id'))->delete();
                }

                $removedCount += $remove->count();
                $groups[] = [
                    'type' => $key->type,
                    'lesson_or_round_id' => (int) $key->lesson_or_round_id,
                    'exam_id' => (int) $key->exam_id,
                    'exam_title' => AdminExamModel::where('id', $key->exam_id)->value('title'),
                    'kept_assign_id' => $keep->id,
                    'removed_assign_ids' => $remove->pluck('id')->values(),
                ];
            }
        });

        return res_data([
            'applied' => $apply,
            'duplicate_exams' => count($groups),
            ($apply ? 'removed_count' : 'would_remove_count') => $removedCount,
            'groups' => $groups,
        ], $apply ? 'تم حذف التكرار' : 'دي التكرارات الموجودة، ابعت apply=1 عشان تتحذف', 200);
    }

    /**
     * Bank exams (from_copy = 0) attached directly to a lesson and/or a round, the old way. A lesson
     * one is hidden from getAllExams; a round one is still in the bank, but deleting it from the round
     * (delete_exam) deletes the bank exam itself. type: lesson (default) | full_round | all.
     * Some were created inside the lesson/round (store_exam_in_round) on purpose, so nothing is
     * changed here, it only lists them.
     */
    public function getLessonLinkedBankExams(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|in:lesson,full_round,all',
        ]);
        $type = $data['type'] ?? 'lesson';

        $assignments = AssignExamModel::when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->whereIn('exam_id', AdminExamModel::where('from_copy', '0')->select('id'))
            ->get();

        $lessons = LessonsModel::with('round_content.round:id,name')
            ->whereIn('id', $assignments->where('type', 'lesson')->pluck('lesson_or_round_id')->unique())
            ->get(['id', 'title', 'round_content_id'])
            ->keyBy('id');
        $rounds = Rounds::whereIn('id', $assignments->where('type', 'full_round')->pluck('lesson_or_round_id')->unique())
            ->get(['id', 'name'])
            ->keyBy('id');

        $exams = AdminExamModel::withCount('questions')
            ->whereIn('id', $assignments->pluck('exam_id')->unique())
            ->orderByDesc('id')
            ->get(['id', 'title', 'exam_label_id', 'created_at']);

        $result = $exams->map(function ($exam) use ($assignments, $lessons, $rounds) {
            $examAssignments = $assignments->where('exam_id', $exam->id);

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'exam_label_id' => $exam->exam_label_id,
                'questions_count' => $exam->questions_count,
                'created_at' => $exam->created_at?->toDateTimeString(),
                'lessons' => $examAssignments->where('type', 'lesson')->map(function ($assign) use ($lessons) {
                    $lesson = $lessons[$assign->lesson_or_round_id] ?? null;
                    $round = $lesson?->round_content?->round;

                    return [
                        'assign_id' => $assign->id,
                        'lesson' => $lesson ? ['id' => $lesson->id, 'title' => $lesson->title] : null,
                        'round' => $round ? ['id' => $round->id, 'name' => $round->name] : null,
                    ];
                })->values(),
                'rounds' => $examAssignments->where('type', 'full_round')->map(function ($assign) use ($rounds) {
                    $round = $rounds[$assign->lesson_or_round_id] ?? null;

                    return [
                        'assign_id' => $assign->id,
                        'round' => $round ? ['id' => $round->id, 'name' => $round->name] : ['id' => (int) $assign->lesson_or_round_id],
                    ];
                })->values(),
            ];
        });

        return res_data($result, 'success', 200);
    }

    /**
     * Separate bank exams from the lessons/rounds they are attached to directly. The exam the
     * lesson/round uses stays attached (so students keep their scores and answers) and becomes the
     * lesson/round copy; a fresh copy with the same questions takes its place in the bank. After this,
     * deleting it from the round no longer touches the bank, and lesson ones show in getAllExams again.
     */
    public function restoreExamsToBank(Request $request)
    {
        $data = $request->validate([
            'exam_ids' => 'required|array|min:1',
            'exam_ids.*' => 'integer|distinct|exists:exams,id',
        ]);

        $restored = DB::transaction(function () use ($data) {
            $restored = [];

            foreach ($data['exam_ids'] as $examId) {
                $exam = AdminExamModel::find($examId);
                $isLinked = AssignExamModel::where('exam_id', $examId)->exists();

                if ($exam->from_copy == '1' || !$isLinked) {
                    continue; // already a copy, or not attached anywhere: nothing to separate
                }

                $examIdMap = [];
                $bankExam = $this->examCopyService->copyExamWithQuestions((int) $examId, $examIdMap);
                $bankExam->from_copy = '0';
                $bankExam->created_at = $exam->created_at; // keep its place in the bank list
                if (AdminExamModel::tracksCopySource()) {
                    $bankExam->copied_from_id = null;
                }
                $bankExam->save();

                if (AdminExamModel::tracksCopySource()) {
                    // The new bank exam is now the source of every copy of this exam, this one included.
                    AdminExamModel::where('copied_from_id', $exam->id)->update(['copied_from_id' => $bankExam->id]);
                    $exam->copied_from_id = $bankExam->id;
                }
                $exam->from_copy = '1';
                $exam->save();

                $restored[] = ['lesson_exam_id' => $exam->id, 'bank_exam_id' => $bankExam->id];
            }

            return $restored;
        });

        return res_data([
            'restored_count' => count($restored),
            'restored' => $restored,
        ], 'تم إرجاع الاختبارات لبنك الاختبارات', 200);
    }



    public function editAssignShowDate(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:assign_exam_round,id',
            'show_date' => 'nullable',
        ]);
        $assign = AssignExamModel::find($data['id']);
        $assign->update([
            'show_date' => $data['show_date'],
        ]);
        return res_data($assign, 'success', 200);
    }




    public function assign_exam_round(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'lesson_or_round_id' => 'required',
            'show_date' => 'nullable',
            'round_exam_section_id' => 'nullable|integer',
        ]);

        $sectionError = RoundExamSectionModel::assignmentError($data['round_exam_section_id'] ?? null, $data['type'], $data['lesson_or_round_id']);
        if ($sectionError) {
            return res_data($sectionError, 'خطأ في التحقق من البيانات', 422);
        }

        return $this->assignCopy($data);
    }

    public function getExamInfo(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);
        $exam = AdminExamModel::findOrFail($data['exam_id']);
        return res_data($exam, 'success', 200);
    }
    public function index()
    {
        return view('courses::index');
    }


    public function create()
    {
        return view('courses::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('courses::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('courses::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
