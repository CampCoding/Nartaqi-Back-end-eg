<?php

namespace Tests\Unit;

use Modules\Courses\Services\RoundCompletionService;
use PHPUnit\Framework\TestCase;

// Module classes are registered at app boot, not by Composer, so load the service directly.
require_once __DIR__ . '/../../Modules/Courses/app/Services/RoundCompletionService.php';

class RoundCompletionServiceTest extends TestCase
{
    private RoundCompletionService $service;

    protected function setUp(): void
    {
        $this->service = new RoundCompletionService();
    }

    public function test_score_to_percentage_handles_fractions_and_plain_values(): void
    {
        $this->assertEqualsWithDelta(90, RoundCompletionService::scoreToPercentage('18/20'), 1e-9);
        $this->assertEqualsWithDelta(45, RoundCompletionService::scoreToPercentage(' 9 / 20 '), 1e-9);
        $this->assertEqualsWithDelta(85.5, RoundCompletionService::scoreToPercentage('85.5'), 1e-9);
        $this->assertSame(0.0, RoundCompletionService::scoreToPercentage('5/0'));
        $this->assertSame(0.0, RoundCompletionService::scoreToPercentage(null));
    }

    public function test_best_attempt_is_compared_numerically_not_alphabetically(): void
    {
        $best = $this->service->pickBest([
            ['student_id' => 1, 'exam_id' => 7, 'score' => '9/20', 'created_at' => '2026-01-01 10:00:00'],
            ['student_id' => 1, 'exam_id' => 7, 'score' => '18/20', 'created_at' => '2026-01-02 10:00:00'],
            ['student_id' => 1, 'exam_id' => 7, 'score' => '18/20', 'created_at' => '2026-01-03 10:00:00'],
            ['student_id' => 2, 'exam_id' => 7, 'score' => '90', 'created_at' => '2026-01-01 10:00:00'],
            ['student_id' => 2, 'exam_id' => 7, 'score' => '100', 'created_at' => '2026-01-02 10:00:00'],
        ]);

        $this->assertEqualsWithDelta(90, $best[1][7]['percentage'], 1e-9);
        $this->assertSame('2026-01-02 10:00:00', $best[1][7]['scored_at'], 'earliest of the tied best attempts');
        $this->assertEqualsWithDelta(100, $best[2][7]['percentage'], 1e-9);
    }

    public function test_breakdown_follows_the_completion_page_math(): void
    {
        $slots = [
            ['type' => 'basic', 'content_id' => 1, 'lesson_id' => 10, 'exam_id' => 100, 'exam_name' => 'B1'],
            ['type' => 'basic', 'content_id' => 1, 'lesson_id' => 11, 'exam_id' => 101, 'exam_name' => 'B2'],
            ['type' => 'lecture', 'content_id' => 2, 'lesson_id' => 20, 'exam_id' => 200, 'exam_name' => 'L1'],
            ['type' => 'full_round', 'content_id' => null, 'lesson_id' => null, 'exam_id' => 300, 'exam_name' => 'F1'],
        ];
        $best = $this->service->pickBest([
            ['student_id' => 5, 'exam_id' => 100, 'score' => '2/3', 'created_at' => '2026-02-01 09:00:00'],
            ['student_id' => 5, 'exam_id' => 200, 'score' => '50', 'created_at' => '2026-02-02 09:00:00'],
            ['student_id' => 5, 'exam_id' => 300, 'score' => '0/10', 'created_at' => '2026-02-03 09:00:00'],
        ])[5];

        $result = $this->service->breakdown($slots, $best);

        // Per-exam scores are rounded first, then averaged per category, then categories are averaged.
        $basic = round((66.67 + 0) / 2, 2);
        $this->assertEquals($basic, $result['basic_percentage']);
        $this->assertEquals(50, $result['lecture_percentage']);
        $this->assertEquals(0, $result['full_round_percentage']);
        $this->assertEquals(round(($basic + 50 + 0) / 3, 2), $result['total_percentage']);

        $this->assertSame('66.67%', $result['items']['basic'][0]['highest_score']);
        $this->assertTrue($result['items']['basic'][0]['solved']);
        $this->assertSame('0%', $result['items']['basic'][1]['highest_score']);
        $this->assertFalse($result['items']['basic'][1]['solved']);
        $this->assertNull($result['items']['basic'][1]['created_at']);
        $this->assertSame('2026-02-02 09:00:00', $result['items']['lecture'][0]['created_at']);
        $this->assertSame('0%', $result['items']['full_round'][0]['highest_score']);
        $this->assertTrue($result['items']['full_round'][0]['solved'], 'an attempt with a zero score still counts as solved');
    }

    public function test_empty_categories_are_ignored_and_no_exams_means_zero(): void
    {
        $slots = [['type' => 'lecture', 'content_id' => 2, 'lesson_id' => 20, 'exam_id' => 200, 'exam_name' => 'L1']];
        $best = $this->service->pickBest([
            ['student_id' => 1, 'exam_id' => 200, 'score' => '4/5', 'created_at' => '2026-03-01 08:00:00'],
        ])[1];

        $this->assertEquals(80, $this->service->breakdown($slots, $best)['total_percentage']);
        $this->assertEquals(0, $this->service->breakdown([], [])['total_percentage']);
    }

    public function test_an_exam_assigned_to_two_lessons_counts_twice(): void
    {
        $slots = [
            ['type' => 'basic', 'content_id' => 1, 'lesson_id' => 10, 'exam_id' => 100, 'exam_name' => 'B1'],
            ['type' => 'basic', 'content_id' => 1, 'lesson_id' => 11, 'exam_id' => 100, 'exam_name' => 'B1'],
            ['type' => 'basic', 'content_id' => 1, 'lesson_id' => 12, 'exam_id' => 101, 'exam_name' => 'B2'],
        ];
        $best = $this->service->pickBest([
            ['student_id' => 1, 'exam_id' => 100, 'score' => '10/10', 'created_at' => '2026-03-01 08:00:00'],
        ])[1];

        $this->assertEquals(round(200 / 3, 2), $this->service->breakdown($slots, $best)['basic_percentage']);
    }
}
