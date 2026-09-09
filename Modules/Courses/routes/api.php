<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authentication;
use App\Http\Middleware\AdminAuthentication;
use Modules\Courses\Http\Controllers\AdminExamSectionController;
use Modules\Courses\Http\Controllers\AdminFreeVideosController;
use Modules\Courses\Http\Controllers\AdminQuestionsController;
use Modules\Courses\Http\Controllers\AssignExamController;
use Modules\Courses\Http\Controllers\CategoryPartsController;
use Modules\Courses\Http\Controllers\GeneralAnswersRatingsController;
use Modules\Courses\Http\Controllers\PaymentConfirmationsAdminController;
use Modules\Courses\Http\Controllers\ResourcesLinksController;
use Modules\Courses\Http\Controllers\RoundLiveController;
use Modules\Courses\Http\Controllers\RoundResourceController;
use Modules\Courses\Http\Controllers\RoundsController;
use Modules\Courses\Http\Controllers\StudentAnswerController;
use Modules\Courses\Http\Controllers\StudentQuestionFolderController;
use Modules\Courses\Http\Controllers\MockExamScratchController;
use Modules\Courses\Http\Controllers\TeacherController;
use Modules\Courses\Http\Controllers\UserCompetitionController;
use Modules\Courses\Http\Controllers\VideosController;
use Modules\Courses\Http\Controllers\AdminRoundController;
use Modules\Courses\Http\Controllers\UserRoundsController;
use Modules\Courses\Http\Controllers\AdminCourseController;
use Modules\Courses\Http\Controllers\AdminLessonsController;
use Modules\Courses\Http\Controllers\ExamController;
use Modules\Courses\Http\Controllers\ExamSectionsController;
use Modules\Courses\Http\Controllers\QuestionsController;
use Modules\Courses\Http\Controllers\RoundFeaturesController;
use Modules\Courses\Http\Controllers\Admin\AdminRoundsController;
use Modules\Courses\Http\Controllers\AdminExamController;
use Modules\Courses\Http\Controllers\AdminRoundContentsController;
use Modules\Courses\Http\Controllers\CoursesController;
use Modules\Courses\Http\Controllers\GeneralRatingsController;
use Modules\Courses\Http\Controllers\CategoryPartFreeController;
use Modules\Courses\Http\Controllers\StudentsController;
use Modules\Courses\Http\Controllers\StudentAchievementResultsController;
use Modules\Courses\Http\Controllers\CompetitionsController;
use Modules\Courses\Http\Controllers\QuestionsBankController;
use Modules\Courses\Http\Controllers\SupportGateController;
use Modules\Courses\Http\Controllers\SupportInfoController;
use Modules\Courses\Http\Controllers\TconditionsController;
use Modules\Courses\Http\Controllers\ExamlabelsController;
use Modules\Courses\Http\Controllers\CourseRequirementsController;
use Modules\Courses\Http\Controllers\QuestionBankPartsController;
use Modules\Courses\Http\Controllers\QuestionBankBranchesController;
use Modules\Courses\Http\Controllers\QuestionBankSkillsController;
use Modules\Courses\Http\Controllers\PlacementTestController;
use Modules\Courses\Http\Controllers\PlacementTestSectionsController;
use Modules\Courses\Http\Controllers\PlacementTestQuestionsController;
use Modules\Courses\Http\Controllers\PlacementTestUserController;
use Modules\Courses\Http\Controllers\PlacementTestSuggestionController;
use Modules\Courses\Http\Controllers\CreateInvoiceController;
use Modules\Courses\Http\Controllers\CreateCartInvoiceController;
use Modules\Courses\Http\Controllers\FawaterakWebhookController;
use Modules\Courses\Http\Controllers\PaymentController;
use Modules\Courses\Http\Controllers\BankAccountsController;
use Modules\Courses\Http\Controllers\PaymentConfirmationsController;
use Modules\Courses\Http\Controllers\CheckCouponController;
use Modules\Courses\Http\Controllers\AdminCouponController;
use Modules\Courses\Http\Controllers\HonorRollController;

Route::prefix('payment')->group(function () {
    Route::post('initiateSession', [PaymentController::class, 'initiateSession']);
    Route::post('initiatePayment', [PaymentController::class, 'initiatePayment']);
    Route::post('getPaymentStatus', [PaymentController::class, 'getPaymentStatus']);
    Route::post('createMyFatoorahSession', [PaymentController::class, 'createMyFatoorahSession']);

    // Callback and Error routes
    Route::get('callback', [PaymentController::class, 'paymentCallback'])->name('myfatoorah.callback');
    Route::get('success', [PaymentController::class, 'paymentCallback']);
    Route::get('failed', [PaymentController::class, 'paymentFailed'])->name('myfatoorah.error');


    // last send v2 with reda 
    Route::post('sendPayment', [PaymentController::class, 'sendPayment']);

    // Fawaterk integration
    Route::post('fawaterk/createInvoice', CreateInvoiceController::class);
    Route::get('fawaterk/success', [FawaterakWebhookController::class, 'successCallback']);
    Route::any('fawaterk/webhook', FawaterakWebhookController::class);

    // Authenticated Student Routes
    Route::middleware(Authentication::class)->group(function () {
        Route::post('fawaterk/payCart', CreateCartInvoiceController::class);
        Route::post('coupons/check', CheckCouponController::class);
    });

    // Admin Coupon Management
    Route::prefix('admin/coupons')->group(function () {
        Route::post('store', [AdminCouponController::class, 'store']);
        Route::get('list', [AdminCouponController::class, 'index']);
        Route::get('rounds', [AdminCouponController::class, 'roundsList']);
        Route::get('store', [AdminCouponController::class, 'storeList']);
        Route::post('update', [AdminCouponController::class, 'update']);
        Route::post('delete', [AdminCouponController::class, 'destroy']);
        Route::post('report', [AdminCouponController::class, 'usageReport']);
    });
});





//

// Flexible token authentication routes (no middleware, manual token handling)
Route::prefix('user/categories')->group(function () {
    Route::post('course-categories', [CoursesController::class, 'get_CourseCategory']);
    Route::post('limit-course-categories', [CoursesController::class, 'get_limit_CourseCategory']);
    Route::post('getAllInHome', [CoursesController::class, 'getAllInHome']);
    Route::get('get_limit_CourseCategoryParts', [CoursesController::class, 'get_limit_CourseCategoryParts']);
    Route::post('makeGeneralSearch', [CoursesController::class, 'makeGeneralSearch']);
    Route::get('getMostCommonSearches', [CoursesController::class, 'getMostCommonSearches']);
    Route::get('getAllCourseCategories', [CoursesController::class, 'getAllCourseCategories']);
    Route::post('getFreeVideos', [RoundsController::class, 'getFreeVideos']);
    Route::get('getCourseCategoryIncludedFreeVideos', [RoundsController::class, 'getCourseCategoryIncludedFreeVideos']);
    Route::get('getGeneralRatingsWithAnswers', [GeneralRatingsController::class, 'getGeneralRatingsWithAnswers']);
    Route::post('studentGeneralRatingAnswers', [GeneralRatingsController::class, 'studentGeneralRatingAnswers']);
    Route::post('getStudentAchievementResults', [RoundsController::class, 'getStudentAchievementResults']);
    Route::get('getTopStudentsByCompetitionType', [UserCompetitionController::class, 'getTopStudentsByCompetitionType']);
    Route::get('getSupportGate', [GeneralRatingsController::class, 'get_support_gate']);
    Route::post('uploadFile', [CoursesController::class, 'uploadFile']);
});

// Route::prefix('user/competitions')->group(function () {
// });


Route::prefix('user/parts')->group(function () {
    Route::post('getCategoryParts', [RoundsController::class, 'getCategoryParts']);
    Route::get('getCategoryPartsWithPlacementTest', [PlacementTestUserController::class, 'getCategoryParts']);
    Route::post('getPlacementTestByCategoryPart', [PlacementTestUserController::class, 'getPlacementTestByCategoryPart']);
    Route::post('getSectionsByPlacementTest', [PlacementTestUserController::class, 'getSectionsByPlacementTest']);
    Route::post('storeScore', [PlacementTestUserController::class, 'storeScore']);
    Route::post('checkIfSolved', [PlacementTestUserController::class, 'checkIfUserSolvedBefore']);
});

Route::prefix('user/settings')->group(function () {
    Route::post('GetStudentExamsWithScores', [GeneralRatingsController::class, 'GetStudentExamsWithScores']);
    Route::get('get_support_gate', [GeneralRatingsController::class, 'get_support_gate']);
    Route::get('getSupportInfo', [SupportInfoController::class, 'getSupportInfo']);
    Route::get('getTconditions', [TconditionsController::class, 'getTconditions']);
    Route::get('getHonorRolls', [HonorRollController::class, 'getHonorRolls']);
    Route::get('getSocialAccounts', [SupportInfoController::class, 'getSocialAccounts']);
    Route::post('makeInquiry', [SupportInfoController::class, 'makeInquiry']);
    Route::get('getCourseRequirements', [GeneralRatingsController::class, 'getCourseRequirements']);
});


Route::prefix('user/rounds')->group(function () {
    Route::get('getRounds', [RoundsController::class, 'getRounds']);
    Route::post('getFreeRounds', [RoundsController::class, 'getFreeRounds']);
    Route::post('getLatestRounds', [RoundsController::class, 'getLatestRounds']);
    Route::post('getmoreLatestRounds', [RoundsController::class, 'getmoreLatestRounds']);
    Route::post('contents', [RoundsController::class, 'getRoundsContents']);
    Route::post('getRoundFeatures', [RoundsController::class, 'getRoundFeatures']);
    Route::post('terms', [RoundsController::class, 'getRoundTerms']);
    Route::post('roundRate', [RoundsController::class, 'roundRate']);
    Route::post('roundBundle', [RoundsController::class, 'getRoundBundle']);
    Route::post('getRoundResources', [RoundsController::class, 'getRoundResources']);
    Route::post('GetAllRoundDataByRoundId', [RoundsController::class, 'GetAllRoundDataByRoundId']);
    Route::post('getRoundByCategoryPart', [RoundsController::class, 'getRoundByCategoryPart']);
    Route::post('getRoundByFilters', [RoundsController::class, 'getRoundByFilters']);
    Route::post('getFreeVideos', [VideosController::class, 'getFreeVideos']);
    Route::post('makestudentView', [RoundsController::class, 'makestudentView']);
    Route::post('getTeacherData', [RoundsController::class, 'getTeacherData']);
    Route::post('makeRoundRate', [RoundsController::class, 'makeRoundRate']);
    Route::post('getRoundContentExamScore', [RoundsController::class, 'getRoundContentExamScore']);
    Route::post('exams/getExamSummaryReport', [StudentAnswerController::class, 'getExamSummaryReport']);
    Route::get('TopRated', [RoundsController::class, 'TopRated']);

    Route::get('getAllTeacher', [RoundsController::class, 'getAllTeacher']);

    Route::prefix('payment-confirmations')->group(function () {
        Route::get('getAllBankAccounts', [PaymentConfirmationsController::class, 'getAllBankAccounts']);
        Route::post('storePaymentConfirmation', [PaymentConfirmationsController::class, 'storePaymentConfirmation']);
    });
});



Route::middleware([Authentication::class])->group(function () {
    Route::prefix('user/rounds')->group(function () {
        Route::post('userCourses', [UserRoundsController::class, 'userCourses']);

        Route::post('getMyCompeletenessRoundByRoundID', [UserRoundsController::class, 'getMyCompeletenessRoundByRoundID']);
        Route::post('enrollInCourse', [UserRoundsController::class, 'enrollInCourse']);
        Route::post('exams/GetRoundExams', [ExamController::class, 'GetRoundExams']);
        Route::post('exams/getFreeRoundExam', [ExamController::class, 'get_free_round_exam']);
        Route::post('exams/get_exam_sectionsWithQuestions', [ExamSectionsController::class, 'get_exam_sectionsWithQuestions']);
        Route::post('exams/get_mock_exam_sectionsWithQuestions', [ExamSectionsController::class, 'get_mock_exam_sectionsWithQuestions']);
        Route::post('exams/save_mock_exam_scratch', [MockExamScratchController::class, 'save']);
        Route::post('exams/getQuestions', [QuestionsController::class, 'get_questions']);
        Route::post('exams/storeStudentAnswers', [StudentAnswerController::class, 'storeStudentAnswers']);
        Route::post('exams/storeStudentScore', [StudentAnswerController::class, 'storeStudentScore']);
        Route::post('exams/getStudentAnswersByExamId', [StudentAnswerController::class, 'getStudentQuestionsWithAnswersByExamId']);
        Route::post('exams/introRound', [RoundsController::class, 'introRound']);
        Route::post('exams/getRoundContentExamScore', [RoundsController::class, 'getRoundContentExamScore']);
        Route::post('exams/getExamSummaryReport', [StudentAnswerController::class, 'getExamSummaryReport']);
    });

    Route::prefix('user/question-folders')->group(function () {
        Route::get('list', [StudentQuestionFolderController::class, 'index']);
        Route::post('store', [StudentQuestionFolderController::class, 'store']);
        Route::post('update', [StudentQuestionFolderController::class, 'update']);
        Route::post('delete', [StudentQuestionFolderController::class, 'destroy']);
        Route::get('show', [StudentQuestionFolderController::class, 'show']);
        Route::post('add_question', [StudentQuestionFolderController::class, 'addQuestion']);
        Route::post('remove_question', [StudentQuestionFolderController::class, 'removeQuestion']);
        Route::get('question_folders', [StudentQuestionFolderController::class, 'questionFolders']);
        Route::post('generate_exam', [StudentQuestionFolderController::class, 'generateExam']);
        Route::get('exam_history', [StudentQuestionFolderController::class, 'examHistory']);
    });

    Route::prefix('user/folder-exams')->group(function () {
        Route::get('show', [StudentQuestionFolderController::class, 'showFolderExam']);
        Route::post('store_answers', [StudentQuestionFolderController::class, 'storeAnswers']);
        Route::post('finish', [StudentQuestionFolderController::class, 'finish']);
    });

    Route::prefix('user/competitions')->group(function () {
        Route::post('getAllCompetitions', [UserCompetitionController::class, 'getAllCompetitions']);

        Route::post('enrollInCompetition', [UserCompetitionController::class, 'enrollInCompetition']);
        Route::post('getCompetitionQuestions', [UserCompetitionController::class, 'getCompetitionQuestions']);
        Route::post('submitAllAnswers', [UserCompetitionController::class, 'submitAllAnswers']);
        Route::post('getStudentInfoScoresPoints', [UserCompetitionController::class, 'getStudentInfoScoresPoints']);
    });
});

Route::middleware(AdminAuthentication::class)->group(function () {
    Route::prefix('admin/exams')->group(function () {
        Route::post('getAllExams', [AdminExamSectionController::class, 'getAllExams']);
        Route::post('getAllExamsByRoundId', [AdminExamSectionController::class, 'getAllExamsByRoundId']);
        Route::post('GetStudentScoresByExamId', [AdminExamSectionController::class, 'GetStudentScoresByExamId']);
        Route::post('assign_intern_exam_round', [AssignExamController::class, 'assign_intern_exam_round']);
        Route::post('assign_exam_round', [AssignExamController::class, 'assign_exam_round']);
        Route::post('editAssignShowDate', [AssignExamController::class, 'editAssignShowDate']);
        Route::post('sortExamRound', [AdminExamSectionController::class, 'sortExamRound']);

        Route::post('GetAllExamsRoundByRoundId', [AdminExamController::class, 'GetAllExamsRoundByRoundId']);
        Route::post('GetAllExamsLessonByLessonId', [AdminExamController::class, 'GetAllExamsLessonByLessonId']);
        Route::post('store_exam', [AdminExamController::class, 'store_exam']);
        Route::post('store_exam_in_round', [AdminExamController::class, 'store_exam_in_round']);
        Route::post('copy_exam', [AdminExamController::class, 'copy_exam']);
        Route::post('edit_exam', [AdminExamController::class, 'edit_exam']);
        Route::post('delete_exam', [AdminExamController::class, 'delete_exam']);
        Route::post('get_exam_all_data_by_id', [AdminExamController::class, 'get_exam_all_data_by_id']);
        Route::post('add_exam_video', [AssignExamController::class, 'add_exam_video']);
        Route::post('edit_exam_video', [AssignExamController::class, 'edit_exam_video']);
        Route::post('delete_exam_video', [AssignExamController::class, 'delete_exam_video']);
        Route::post('add_exam_pdf', [AssignExamController::class, 'add_exam_pdf']);
        Route::post('edit_exam_pdf', [AssignExamController::class, 'edit_exam_pdf']);
        Route::post('delete_exam_pdf', [AssignExamController::class, 'delete_exam_pdf']);

        Route::post('getExamInfo', [AssignExamController::class, 'getExamInfo']);



        Route::prefix('exam-sections')->group(function () {
            Route::post('GetAllExamSectionsByExamId', [AdminExamSectionController::class, 'GetAllExamSectionsByExamId']);
            Route::post('store_exam_section', [AdminExamSectionController::class, 'store_exam_section']);
            Route::post('edit_exam_section', [AdminExamSectionController::class, 'edit_exam_section']);
            Route::post('delete_exam_section', [AdminExamSectionController::class, 'delete_exam_section']);
        });
    });

    Route::prefix('admin/questions')->group(function () {
        Route::post('get_questions', [AdminQuestionsController::class, 'get_questions']);
        Route::post('StoreQuestionWithAnswers', [AdminQuestionsController::class, 'StoreQuestionWithAnswers']);
        Route::post('StoreParagraphQuestionWithAnswers', [AdminQuestionsController::class, 'StoreParagraphQuestionWithAnswers']);
        Route::post('edit_question', [AdminQuestionsController::class, 'editQuestion']);
        Route::post('delete_question', [AdminQuestionsController::class, 'deleteQuestion']);

        Route::post('editParagraphQuestions', [AdminQuestionsController::class, 'editParagraphQuestions']);
        Route::post('deleteParagraphQuestions', [AdminQuestionsController::class, 'deleteParagraphQuestions']);
        Route::post('makeAutoGenerateQuestions', [AdminQuestionsController::class, 'makeAutoGenerateQuestions']);
        Route::post('upload_audio', [AdminQuestionsController::class, 'uploadAudio']);
    });
});




Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/categories')->group(function () {
        Route::get('get_all_course_categories', [AdminCourseController::class, 'get_all_course_categories']);
        Route::post('makeSortCourseCategories', [AdminCourseController::class, 'makeSortCourseCategories']);

        Route::post('store_course_category', [AdminCourseController::class, 'store_course_category']);
        Route::get('getCourseCategoriesWithParts', [AdminCourseController::class, 'getCourseCategoriesWithParts']);
        Route::post('edit_course_category', [AdminCourseController::class, 'edit_course_category']);
        Route::post('active_course_category', [AdminCourseController::class, 'active_course_category']);
        Route::post('delete_course_category', [AdminCourseController::class, 'delete_course_category']);
        Route::post('makeSort', [AdminCourseController::class, 'makeSort']);
        Route::post('parts/add_category_part', [CategoryPartsController::class, 'add_category_part']);
        Route::post('parts/edit_category_part', [CategoryPartsController::class, 'edit_category_part']);
        Route::post('parts/delete_category_part', [CategoryPartsController::class, 'delete_category_part']);
        Route::post('parts/get_category_parts_by_course_category_id', [CategoryPartsController::class, 'get_category_parts_by_course_category_id']);
        Route::post('parts/makeSort', [CategoryPartsController::class, 'makeSort']);

        Route::post('free_videos/get_all_free_videos', [AdminFreeVideosController::class, 'get_all_free_videos']);
        Route::post('free_videos/add_free_video', [AdminFreeVideosController::class, 'add_free_video']);
        Route::post('free_videos/edit_free_video', [AdminFreeVideosController::class, 'edit_free_video']);
        Route::post('free_videos/delete_free_video', [AdminFreeVideosController::class, 'delete_free_video']);
        Route::post('free_videos/makeSortVideos', [AdminFreeVideosController::class, 'makeSortVideos']);
        // Route::post('free_videos/getVideoData', [AdminFreeVideosController::class, 'getVideoData']);
    });

    Route::prefix('admin/category-parts-free')->group(function () {
        Route::get('get_all', [CategoryPartFreeController::class, 'get_all']);
        Route::post('store', [CategoryPartFreeController::class, 'store']);
        Route::post('makeSortPartFree', [CategoryPartFreeController::class, 'makeSortPartFree']);

        Route::post('update', [CategoryPartFreeController::class, 'update']);
        Route::post('delete', [CategoryPartFreeController::class, 'delete']);
    });


    Route::prefix('admin/rounds')->group(function () {
        Route::get('get_all_rounds', [AdminRoundController::class, 'get_all_rounds']);
        Route::post('store_round', [AdminRoundController::class, 'store_round']);
        Route::post('makeCopyRoundWithAllData', [AdminRoundController::class, 'makeCopyRoundWithAllData']);
        Route::post('copyRoundContent', [AdminRoundController::class, 'copyRoundContent']);
        Route::post('edit_round', [AdminRoundController::class, 'edit_round']);
        Route::post('active_round', [AdminRoundController::class, 'active_round']);
        Route::post('delete_round', [AdminRoundController::class, 'delete_round']);
        Route::post('addRoundTerm', [RoundsController::class, 'addRoundTerm']);
        Route::post('editRoundTerm', [RoundsController::class, 'editRoundTerm']);
        Route::post('deleteRoundTerm', [RoundsController::class, 'deleteRoundTerm']);
        Route::get('getsourceRound', [AdminRoundController::class, 'getsourceRound']);
        Route::post('getRoundFreeVideos', [AdminRoundController::class, 'getRoundFreeVideos']);

        Route::post('getAllStudentInRound', [AdminRoundController::class, 'getAllStudentInRound']);
        Route::post('getStudentRateRound', [AdminRoundController::class, 'getStudentRateRound']);
        Route::post('showHiddenStudentRate', [AdminRoundController::class, 'showHiddenStudentRate']);

        Route::post('get_solo_round_data', [AdminRoundController::class, 'get_solo_round_data']);
        Route::post('toggleShowRoundBook', [AdminRoundController::class, 'toggleShowRoundBook']);
    });
    Route::prefix('admin/roundsLives')->group(function () {
        Route::post('get_all_round_lives', [RoundLiveController::class, 'get_all_round_lives']);
        Route::post('store_round_live', [RoundLiveController::class, 'store_round_live']);
        Route::post('edit_round_live', [RoundLiveController::class, 'edit_round_live']);
        Route::post('delete_round_live', [RoundLiveController::class, 'delete_round_live']);
        Route::post('active_in_active_round_live', [RoundLiveController::class, 'activeInActiveRoundLive']);
        Route::post('makeLiveFinished', [RoundLiveController::class, 'makeLiveFinished']);
    });
    Route::prefix('admin/students')->group(function () {
        Route::post('get_all_students', [StudentsController::class, 'get_all_students']);
        Route::post('get_student_by_phone', [StudentsController::class, 'get_student_by_phone']);
        Route::post('get_student_rounds', [StudentsController::class, 'get_student_rounds']);
        Route::post('inroll_in_round', [StudentsController::class, 'inroll_in_round']);
        Route::post('cancel_inroll_from_round', [StudentsController::class, 'cancel_inroll_from_round']);
        Route::get('get_all_active_rounds', [StudentsController::class, 'get_all_active_rounds']);
    });

    Route::prefix('admin/teachers')->group(function () {
        Route::get('getTeachers', [TeacherController::class, 'getTeachers']);
        Route::post('add_teacher', [TeacherController::class, 'add_teacher']);
        Route::post('edit_teacher', [TeacherController::class, 'edit_teacher']);
        Route::post('delete_teacher', [TeacherController::class, 'delete_teacher']);
    });


    Route::prefix('admin/rounds-contents')->group(function () {
        Route::post('get_all_round_contents', [AdminRoundContentsController::class, 'get_all_round_contents']);
        Route::post('store_round_content', [AdminRoundContentsController::class, 'store_round_content']);
        Route::post('edit_round_content', [AdminRoundContentsController::class, 'edit_round_content']);
        Route::post('delete_round_content', [AdminRoundContentsController::class, 'delete_round_content']);

        Route::post('makeSortCourseCategory', [AdminRoundContentsController::class, 'makeSortCourseCategory']);
    });

    Route::prefix('admin/rounds-resources')->group(function () {
        Route::post('getRoundResources', [RoundResourceController::class, 'getRoundResources']);
        Route::post('editRoundResource', [RoundResourceController::class, 'editRoundResource']);
        Route::post('addRoundResource', [RoundResourceController::class, 'addRoundResource']);
        Route::post('deleteRoundResource', [RoundResourceController::class, 'deleteRoundResource']);

        Route::post('getResourceLinks', [ResourcesLinksController::class, 'getResourceLinks']);
        Route::post('storeResourceLinks', [ResourcesLinksController::class, 'storeResourceLinks']);
        Route::post('editResourceLinks', [ResourcesLinksController::class, 'editResourceLinks']);
        Route::post('deleteResourceLinks', [ResourcesLinksController::class, 'deleteResourceLinks']);
    });


    Route::prefix('admin/contents/lessons')->group(function () {
        Route::post('get_all_lessons', [AdminLessonsController::class, 'get_all_lessons']);
        Route::post('get_all_lessons_by_round', [AdminLessonsController::class, 'get_all_lessons_by_round']);
        Route::post('store_lesson', [AdminLessonsController::class, 'store_lesson']);
        Route::post('edit_lesson', [AdminLessonsController::class, 'edit_lesson']);
        Route::post('delete_lesson', [AdminLessonsController::class, 'delete_lesson']);
    });


    Route::prefix('admin/contents/lessons/videos')->group(function () {
        Route::post('get_all_videos', [VideosController::class, 'get_all_videos']);
        Route::post('add_video', [VideosController::class, 'add_video']);
        Route::post('edit_video', [VideosController::class, 'edit_video']);
        Route::post('delete_video', [VideosController::class, 'delete_video']);
    });

    Route::prefix('admin/rounds/features')->group(function () {
        Route::post('get_all_round_features', [RoundFeaturesController::class, 'get_all_round_features']);
        Route::post('add_round_feature', [RoundFeaturesController::class, 'add_round_feature']);
        Route::post('edit_round_feature', [RoundFeaturesController::class, 'edit_round_feature']);
        Route::post('delete_round_feature', [RoundFeaturesController::class, 'delete_round_feature']);
    });

    Route::prefix('admin/general-ratings')->group(function () {
        Route::get('index', [GeneralRatingsController::class, 'index']);
        Route::post('store', [GeneralRatingsController::class, 'store']);
        Route::post('show', [GeneralRatingsController::class, 'show']);
        Route::post('update', [GeneralRatingsController::class, 'update']);
        Route::post('delete', [GeneralRatingsController::class, 'destroy']);
    });

    Route::prefix('admin/student-inquiries')->group(function () {
        Route::get('getStudentInquiries', [GeneralRatingsController::class, 'getStudentInquiries']);
        Route::post('markInquiryAsSolved', [GeneralRatingsController::class, 'markInquiryAsSolved']);
    });

    Route::prefix('admin/general-answers-ratings')->group(function () {
        Route::post('index', [GeneralAnswersRatingsController::class, 'index']);
        Route::post('store', [GeneralAnswersRatingsController::class, 'store']);
        Route::post('show', [GeneralAnswersRatingsController::class, 'show']);
        Route::post('update', [GeneralAnswersRatingsController::class, 'update']);
        Route::post('delete', [GeneralAnswersRatingsController::class, 'destroy']);
    });

    Route::prefix('admin/student-achievement-results')->group(function () {
        Route::get('getAllCategoryPart', [StudentAchievementResultsController::class, 'getAllCategoryPart']);
        Route::get('getCategoryPartsSortedForStudentDegree', [StudentAchievementResultsController::class, 'getCategoryPartsSortedForStudentDegree']);
        Route::post('makeSortCategoryParts', [StudentAchievementResultsController::class, 'makeSortCategoryParts']);
        Route::post('deletestudent_achievement_results_count', [StudentAchievementResultsController::class, 'deletestudent_achievement_results_count']);

        Route::post('getAllStudentAchievementResults', [StudentAchievementResultsController::class, 'getAllStudentAchievementResults']);
        Route::post('getStudentAchievementResultById', [StudentAchievementResultsController::class, 'getStudentAchievementResultById']);
        Route::post('getStudentAchievementResultsByCategoryPart', [StudentAchievementResultsController::class, 'getStudentAchievementResultsByCategoryPart']);
        Route::post('addStudentAchievementResults', [StudentAchievementResultsController::class, 'addStudentAchievementResults']);
        Route::post('editStudentAchievementResults', [StudentAchievementResultsController::class, 'editStudentAchievementResults']);
        Route::post('deleteStudentAchievementResults', [StudentAchievementResultsController::class, 'deleteStudentAchievementResults']);
        Route::post('makeSortStudentAchievementResults', [StudentAchievementResultsController::class, 'makeSortStudentAchievementResults']);
    });

    Route::prefix('admin/competitions')->group(function () {
        Route::get('getAllCompetitions', [CompetitionsController::class, 'getAllCompetitions']);
        Route::post('getComputationFullData', [CompetitionsController::class, 'getComputationFullData']);
        Route::post('getStudentWithPoints', [CompetitionsController::class, 'getStudentWithPoints']);

        Route::get('getActiveCompetitions', [CompetitionsController::class, 'getActiveCompetitions']);
        Route::post('showCompetition', [CompetitionsController::class, 'showCompetition']);
        Route::post('storeCompetition', [CompetitionsController::class, 'storeCompetition']);
        Route::post('updateCompetition', [CompetitionsController::class, 'updateCompetition']);
        Route::post('deleteCompetition', [CompetitionsController::class, 'deleteCompetition']);
        Route::post('toggleCompetitionStatus', [CompetitionsController::class, 'toggleCompetitionStatus']);
        Route::post('makeAutoGenerateQuestions', [CompetitionsController::class, 'makeAutoGenerateQuestions']);
        Route::post('updateComputationQuestions', [CompetitionsController::class, 'updateComputationQuestions']);
        Route::post('deleteComputationQuestions', [CompetitionsController::class, 'deleteComputationQuestions']);
        Route::post('getComputationQuestions', [CompetitionsController::class, 'getComputationQuestions']);
        Route::post('addSingleQuestion', [CompetitionsController::class, 'addSingleQuestion']);
    });


    Route::prefix('admin/questions-bank')->group(function () {
        Route::post('getAllQuestions', [QuestionsBankController::class, 'getAllQuestions']);
        Route::post('getQuestionsByType', [QuestionsBankController::class, 'getQuestionsByType']);
        Route::post('showQuestion', [QuestionsBankController::class, 'showQuestion']);
        Route::post('storeQuestion', [QuestionsBankController::class, 'storeQuestion']);
        Route::post('StoreQuestionWithAnswers', [QuestionsBankController::class, 'StoreQuestionWithAnswers']);
        Route::post('updateQuestion', [QuestionsBankController::class, 'updateQuestion']);
        Route::post('updateQuestionText', [QuestionsBankController::class, 'updateQuestionText']);
        Route::post('updateQuestionOptions', [QuestionsBankController::class, 'updateQuestionOptions']);
        Route::post('deleteQuestion', [QuestionsBankController::class, 'deleteQuestion']);
        Route::post('editParagraphQuestionsBank', [QuestionsBankController::class, 'editParagraphQuestionsBank']);
        Route::post('deleteParagraphQuestionsBank', [QuestionsBankController::class, 'deleteParagraphQuestionsBank']);
        Route::post('storeQbankParagraphQuestionWithAnswers', [QuestionsBankController::class, 'storeQbankParagraphQuestionWithAnswers']);

        // New Routes For Hierarchy Navigation (with counts)
        Route::get('getQuestionBankParts', [QuestionsBankController::class, 'getQuestionBankParts']);
        Route::post('getQuestionBankBranchesByPartId', [QuestionsBankController::class, 'getQuestionBankBranchesByPartId']);
        Route::post('getQuestionBankSkillsByBranchId', [QuestionsBankController::class, 'getQuestionBankSkillsByBranchId']);
    });

    Route::prefix('admin/question-bank-parts')->group(function () {
        Route::get('getQuestionBankParts', [QuestionBankPartsController::class, 'getQuestionBankParts']);
        Route::post('addQuestionBankParts', [QuestionBankPartsController::class, 'addQuestionBankParts']);
        Route::post('editQuestionBankParts', [QuestionBankPartsController::class, 'editQuestionBankParts']);
        Route::post('deleteQuestionBankParts', [QuestionBankPartsController::class, 'deleteQuestionBankParts']);
    });

    Route::prefix('admin/question-bank-branches')->group(function () {
        Route::get('getQuestionBankBranches', [QuestionBankBranchesController::class, 'getQuestionBankBranches']);
        Route::post('getQuestionBankBranchesByPartId', [QuestionBankBranchesController::class, 'getQuestionBankBranchesByPartId']);
        Route::post('addQuestionBankBranches', [QuestionBankBranchesController::class, 'addQuestionBankBranches']);
        Route::post('editQuestionBankBranches', [QuestionBankBranchesController::class, 'editQuestionBankBranches']);
        Route::post('deleteQuestionBankBranches', [QuestionBankBranchesController::class, 'deleteQuestionBankBranches']);
    });

    Route::prefix('admin/question-bank-skills')->group(function () {
        Route::get('getQuestionBankSkills', [QuestionBankSkillsController::class, 'getQuestionBankSkills']);
        Route::post('addQuestionBankSkills', [QuestionBankSkillsController::class, 'addQuestionBankSkills']);
        Route::post('editQuestionBankSkills', [QuestionBankSkillsController::class, 'editQuestionBankSkills']);
        Route::post('deleteQuestionBankSkills', [QuestionBankSkillsController::class, 'deleteQuestionBankSkills']);
        Route::post('getQuestionBankSkillsByBranchId', [QuestionBankSkillsController::class, 'getQuestionBankSkillsByBranchId']);
    });

    Route::prefix('admin/support')->group(function () {
        Route::get('getSupportGate', [SupportGateController::class, 'getSupportGate']);
        Route::post('addSupportGate', [SupportGateController::class, 'addSupportGate']);
        Route::post('updateSupportGate', [SupportGateController::class, 'updateSupportGate']);
        Route::post('deleteSupportGate', [SupportGateController::class, 'deleteSupportGate']);
        Route::post('updateStudentPassword', [SupportGateController::class, 'updateStudentPassword']);



        Route::get('getSupportInfo', [SupportInfoController::class, 'getSupportInfo']);
        // Route::post('addSupportInfo', [SupportInfoController::class, 'addSupportInfo']);
        Route::post('updateSupportInfo', [SupportInfoController::class, 'updateSupportInfo']);

        Route::get('getSocialAccounts', [SupportInfoController::class, 'getSocialAccounts']);
        Route::post('addSocialAccount', [SupportInfoController::class, 'addSocialAccount']);
        Route::post('updateSocialAccount', [SupportInfoController::class, 'updateSocialAccount']);
        Route::post('deleteSocialAccount', [SupportInfoController::class, 'deleteSocialAccount']);
    });

    Route::prefix('admin/term-conditions')->group(function () {
        Route::get('getTconditions', [TconditionsController::class, 'getTconditions']);
        Route::post('updateTconditions', [TconditionsController::class, 'updateTconditions']);
    });

    Route::prefix('admin/honor-rolls')->group(function () {
        Route::get('getHonorRolls', [HonorRollController::class, 'getHonorRolls']);
        Route::post('storeHonorRoll', [HonorRollController::class, 'storeHonorRoll']);
        Route::post('makeSortHonorRoll', [HonorRollController::class, 'makeSortHonorRoll']);
        Route::post('updateHonorRoll', [HonorRollController::class, 'updateHonorRoll']);
        Route::post('deleteHonorRoll', [HonorRollController::class, 'deleteHonorRoll']);
    });

    Route::prefix('admin/exam-labels')->group(function () {
        Route::get('getExamLabels', [ExamlabelsController::class, 'getExamLabels']);
        Route::post('addExamLabel', [ExamlabelsController::class, 'addExamLabel']);
        Route::post('updateExamLabel', [ExamlabelsController::class, 'updateExamLabel']);
        Route::post('deleteExamLabel', [ExamlabelsController::class, 'deleteExamLabel']);
    });

    Route::prefix('admin/course-requirements')->group(function () {
        Route::get('getCourseRequirements', [CourseRequirementsController::class, 'getCourseRequirements']);
        Route::post('addCourseRequirements', [CourseRequirementsController::class, 'addCourseRequirements']);
        Route::post('updateCourseRequirements', [CourseRequirementsController::class, 'updateCourseRequirements']);
        Route::post('deleteCourseRequirements', [CourseRequirementsController::class, 'deleteCourseRequirements']);
    });
    Route::prefix('admin/placement-tests')->group(function () {
        Route::get('getCategoryPartsSortedForPlacementTest', [PlacementTestController::class, 'getCategoryPartsSortedForPlacementTest']);
        Route::post('makeSort', [PlacementTestController::class, 'makeSort']);
        Route::post('getPlacementTestByCategoryPart', [PlacementTestController::class, 'getPlacementTestByCategoryPart']);
        Route::post('storePlacementTest', [PlacementTestController::class, 'storePlacementTest']);
        Route::post('editPlacementTest', [PlacementTestController::class, 'editPlacementTest']);
        Route::post('deletePlacementTest', [PlacementTestController::class, 'deletePlacementTest']);
    });

    Route::prefix('admin/placement-test-sections')->group(function () {
        Route::post('getSectionByExam', [PlacementTestSectionsController::class, 'getSectionByExam']);
        Route::post('storeSection', [PlacementTestSectionsController::class, 'storeSection']);
        Route::post('editSection', [PlacementTestSectionsController::class, 'editSection']);
        Route::post('deleteSection', [PlacementTestSectionsController::class, 'deleteSection']);
    });

    Route::prefix('admin/placement-test-questions')->group(function () {
        Route::post('getPlacementTestQuestions', [PlacementTestQuestionsController::class, 'getPlacementTestQuestions']);
        Route::post('storePlacementTestQuestionWithAnswers', [PlacementTestQuestionsController::class, 'storePlacementTestQuestionWithAnswers']);
        Route::post('storePlacementTestParagraphQuestionWithAnswers', [PlacementTestQuestionsController::class, 'storePlacementTestParagraphQuestionWithAnswers']);
        Route::post('editPlacementTestQuestion', [PlacementTestQuestionsController::class, 'editPlacementTestQuestion']);
        Route::post('editPlacementTestParagraph', [PlacementTestQuestionsController::class, 'editPlacementTestParagraph']);
        Route::post('deletePlacementTestParagraph', [PlacementTestQuestionsController::class, 'deletePlacementTestParagraph']);
        Route::post('deletePlacementTestQuestion', [PlacementTestQuestionsController::class, 'deletePlacementTestQuestion']);
        Route::post('makeAutoGenerateQuestions', [PlacementTestQuestionsController::class, 'makeAutoGenerateQuestions']);
    });
    Route::prefix('admin/placement-test-suggestions')->group(function () {
        Route::post('storeSuggestion', [PlacementTestSuggestionController::class, 'storeSuggestion']);
        Route::post('editSuggestion', [PlacementTestSuggestionController::class, 'editSuggestion']);
        Route::post('deleteSuggestion', [PlacementTestSuggestionController::class, 'deleteSuggestion']);
        Route::post('selectSuggestionByPlacementTest', [PlacementTestSuggestionController::class, 'selectSuggestionByPlacementTest']);
        Route::get('get_all_rounds', [PlacementTestSuggestionController::class, 'get_all_rounds']);
    });

    Route::prefix('admin/bank-accounts')->group(function () {
        Route::get('getBankAccounts', [BankAccountsController::class, 'getBankAccounts']);
        Route::post('addBankAccount', [BankAccountsController::class, 'addBankAccount']);
        Route::post('editBankAccount', [BankAccountsController::class, 'editBankAccount']);
        Route::post('deleteBankAccount', [BankAccountsController::class, 'deleteBankAccount']);
    });

    Route::prefix('admin/paymentConfirmations')->group(function () {
        Route::get('students-with-payments', [PaymentConfirmationsAdminController::class, 'studentsWithPayments']);
        Route::post('change-status', [PaymentConfirmationsAdminController::class, 'changeStatus']);
    });
});
