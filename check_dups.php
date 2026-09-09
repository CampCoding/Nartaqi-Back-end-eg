<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
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
$usedQuestionTexts = \Modules\Courses\Models\PlacementTestQuestionsModel::where('placement_test_section_id', 5) // or another section
    ->whereNull('paragraph_id')
    ->whereNotNull('question_text')
    ->pluck('question_text')
    ->filter(function($text) { return !empty(trim($text)) && trim(strip_tags($text)) !== ''; })
    ->toArray();
$bankQuestions = \Modules\Courses\Models\QuestionsBankModel::with('options')
    ->where('question_bank_skills_id', $skillId)
    ->when(!empty($usedQuestionTexts), function($query) use ($usedQuestionTexts) {
        return $query->whereNotIn('question_text', $usedQuestionTexts);
    })
    ->where('type', 'mcq')
    ->count();
echo "Bank available before: " . count($usedQuestionTexts) . " after: $bankQuestions\n";
