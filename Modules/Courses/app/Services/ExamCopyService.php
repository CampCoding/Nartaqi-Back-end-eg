<?php

namespace Modules\Courses\Services;

use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AdminExamSectionModel;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\QuestionAnswersModel;
use Modules\Courses\Models\QuestionOptionsModel;
use Modules\Courses\Models\QuestionParagraphsModel;

class ExamCopyService
{
    /**
     * Copy an exam with all its sections, paragraphs, questions, options and answers.
     * $examIdMap is shared across a single copy operation so the same source exam
     * is never copied twice (e.g. when it's assigned to more than one lesson).
     */
    public function copyExamWithQuestions(int $examId, array &$examIdMap): ?AdminExamModel
    {
        if (isset($examIdMap[$examId])) {
            return AdminExamModel::find($examIdMap[$examId]);
        }

        $originalExam = AdminExamModel::find($examId);
        if (!$originalExam) {
            return null;
        }

        $newExam = $originalExam->replicate();
        $newExam->from_copy = '1';
        $newExam->created_at = now();
        $newExam->updated_at = now();
        $newExam->save();

        $examIdMap[$examId] = $newExam->id;

        $sections = AdminExamSectionModel::where('exam_id', $examId)->get();

        foreach ($sections as $section) {
            $newSection = $section->replicate();
            $newSection->exam_id = $newExam->id;
            $newSection->created_at = now();
            $newSection->updated_at = now();
            $newSection->save();

            $paragraphMap = [];
            $paragraphs = QuestionParagraphsModel::where('exam_section_id', $section->id)->get();
            foreach ($paragraphs as $paragraph) {
                $newParagraph = $paragraph->replicate();
                $newParagraph->exam_section_id = $newSection->id;
                // Legacy column, unused by the current paragraph->questions flow; replicate()
                // would otherwise carry over the source row's value and leave the copy's
                // paragraph pointing at the *original* question, which cascade-deletes the
                // copy if that original question (or its paragraph) is later removed.
                // Some environments already dropped this column manually, so only touch it
                // when it's actually present, otherwise the INSERT fails with "Unknown column".
                if (array_key_exists('question_id', $paragraph->getAttributes())) {
                    $newParagraph->question_id = null;
                }
                $newParagraph->save();
                $paragraphMap[$paragraph->id] = $newParagraph->id;
            }

            $questions = AdminQuestionsModel::where('exam_section_id', $section->id)->get();
            foreach ($questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->exam_section_id = $newSection->id;

                if ($question->paragraph_id && isset($paragraphMap[$question->paragraph_id])) {
                    $newQuestion->paragraph_id = $paragraphMap[$question->paragraph_id];
                } elseif ($question->paragraph_id) {
                    $newQuestion->paragraph_id = null;
                }

                $newQuestion->created_at = now();
                $newQuestion->updated_at = now();
                $newQuestion->save();

                $optionMap = [];
                $options = QuestionOptionsModel::where('question_id', $question->id)->get();
                foreach ($options as $option) {
                    $newOption = $option->replicate();
                    $newOption->question_id = $newQuestion->id;
                    $newOption->save();
                    $optionMap[$option->id] = $newOption->id;
                }

                $answer = QuestionAnswersModel::where('question_id', $question->id)->first();
                if ($answer) {
                    $newAnswer = $answer->replicate();
                    $newAnswer->question_id = $newQuestion->id;
                    $newAnswer->correct_option_id = $optionMap[$answer->correct_option_id] ?? null;
                    $newAnswer->save();
                }
            }
        }

        return $newExam;
    }
}
