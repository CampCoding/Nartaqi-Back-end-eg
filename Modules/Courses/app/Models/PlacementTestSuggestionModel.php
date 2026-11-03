<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestSuggestionModelFactory;

class PlacementTestSuggestionModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_suggestion';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'placement_test_id',
        'from_score',
        'to_score',
        'message',
        'suggestion_round_id',
    ];

    public function placementTest()
    {
        return $this->belongsTo(PlacementTestModel::class, 'placement_test_id');
    }

    public function suggestionRound()
    {
        return $this->belongsTo(Rounds::class, 'suggestion_round_id');
    }

    /**
     * The suggestion (with its round) whose score range contains $score, or null.
     */
    public static function forScore(int $placementTestId, ?string $score): ?self
    {
        $degree = PlacementTestStudentScoresModel::degreeOf($score);

        $suggestion = static::where('placement_test_id', $placementTestId)
            ->where('from_score', '<=', $degree)
            ->where('to_score', '>=', $degree)
            ->with(['suggestionRound' => function ($query) {
                $query->select('id', 'name', 'image');
            }])
            ->first();

        if ($suggestion && $suggestion->suggestionRound) {
            $suggestion->suggestionRound->setVisible(['id', 'name', 'image_url'])->setAppends(['image_url']);
        }

        return $suggestion;
    }
}
