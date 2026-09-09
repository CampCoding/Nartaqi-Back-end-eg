<?php
$skillId = 5;
$mcqTotal = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)->where('type', 'mcq')->count();
$uniqueTexts = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)->where('type', 'mcq')->distinct('question_text')->count('question_text');
$usedInTest = \Modules\Courses\Models\AdminQuestionsModel::whereNull('paragraph_id')->whereNotNull('question_text')->pluck('question_text')->toArray();
$usedClean = array_filter($usedInTest, function($text) { return !empty(trim($text)) && trim(strip_tags($text)) !== ''; });
echo "\n====================\n";
echo "Total MCQ: $mcqTotal\n";
echo "Unique Texts: $uniqueTexts\n";
echo "Used in section raw: " . count($usedInTest) . "\n";
echo "Used in section clean: " . count($usedClean) . "\n";
$availRaw = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)->where('type', 'mcq')->when(count($usedInTest), fn($q)=>$q->whereNotIn('question_text', $usedInTest))->count();
$availClean = \Modules\Courses\Models\QuestionsBankModel::where('question_bank_skills_id', $skillId)->where('type', 'mcq')->when(count($usedClean), fn($q)=>$q->whereNotIn('question_text', $usedClean))->count();
echo "Avail raw: $availRaw\n";
echo "Avail clean: $availClean\n";
echo "====================\n";
