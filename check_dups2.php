<?php
$skillId = 5;
$duplicates = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)
    ->where('type', 'mcq')
    ->selectRaw('question_text, count(*) as count')
    ->groupBy('question_text')
    ->havingRaw('count(*) > 1')
    ->orderByDesc('count')
    ->limit(10)
    ->get();
foreach($duplicates as $d) {
    echo "Count: " . $d->count . " -> " . strip_tags($d->question_text) . "\n";
}
// Using PlacementTest just as a representative example to see bank behavior
// I'll just use a mock array to test Bank available
$usedQuestionTexts = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)->where('type', 'mcq')->limit(300)->pluck('question_text')->toArray();

$bankQuestions = \Modules\Courses\Models\QuestionsBankModel::with('options')
    ->where('question_bank_skills_id', $skillId)
    ->when(!empty($usedQuestionTexts), function($query) use ($usedQuestionTexts) {
        return $query->whereNotIn('question_text', $usedQuestionTexts);
    })
    ->where('type', 'mcq')
    ->count();
echo "Bank available before simulated 300: " . count($usedQuestionTexts) . " after: $bankQuestions\n";
