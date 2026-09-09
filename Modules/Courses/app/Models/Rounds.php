<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Courses\Models\UserRounds;
// use Modules\Courses\Database\Factories\RoundsFactory;
use Carbon\Carbon;

class Rounds extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'image',
        'active',
        'course_category_id',
        'price',
        'start_date',
        'end_date',
        'gender',
        'for',
        'goal',
        'teacher_id',
        'category_part_id',
        'source',
        'round_type',
        'created_at',
        'updated_at',
        'deleted_at',
        'free',
        'capacity',
        'time_show',
        "round_road_map_book",
        "round_book",
        "have_certificate",
        "category_part_free_id",
        "in_store",
        "show_round_book"
    ];
    protected $casts = [
        'price' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    public $table = 'rounds';

    public $appends = ['image_url', 'total_days', 'total_hours', 'teachers', 'round_road_map_book_url', 'round_book_url'];

    public function getRoundRoadMapBookUrlAttribute()
    {
        $rawValue = $this->attributes['round_road_map_book'] ?? null;
        if (!$rawValue) {
            return null;
        }
        return asset('storage/' . $rawValue);
    }

    public function getRoundBookUrlAttribute()
    {
        $rawValue = $this->attributes['round_book'] ?? null;
        if (!$rawValue) {
            return null;
        }
        return asset('storage/' . $rawValue);
    }



    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }


    public function getTeachersAttribute()
    {
        if (!$this->teacher_id) {
            return [];
        }

        // convert "1,2,3" → [1,2,3]
        $ids = explode(',', $this->teacher_id);

        return TeachersModel::whereIn('id', $ids)->get();
    }



    public function getTotalDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return Carbon::parse($this->start_date)
            ->diffInDays(Carbon::parse($this->end_date)) + 1;
    }

    public function getTotalHoursAttribute()
    {
        $totalSeconds = $this->round_contents()
            ->with('lessons.videos')
            ->get()
            ->flatMap(fn($content) => $content->lessons)
            ->flatMap(fn($lesson) => $lesson->videos)
            ->sum(function ($video) {
                return is_numeric($video->time) ? (int) $video->time : 0;
            });

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function course_categories()
    {
        return $this->belongsTo(CourseCategories::class, 'course_category_id');
    }
    public function round_contents()
    {
        return $this->hasMany(RoundContetModel::class, 'round_id');
    }

    public function teacher()
    {
        return $this->belongsTo(TeachersModel::class, 'teacher_id');
    }

    public function category_parts()
    {
        return $this->belongsTo(CategoryPartsModel::class, 'category_part_id');
    }

    public function round_features()
    {
        return $this->hasMany(RoundFeaturesModel::class, 'round_id');
    }

    public function round_resources()
    {
        return $this->hasMany(RoundResouceModel::class, 'round_id');
    }

    public function round_terms()
    {
        return $this->hasMany(RoundTerm::class, 'round_id');
    }

    public function students_rates()
    {
        return $this->hasMany(StudentsRateModel::class, 'round_id');
    }

    public function userRounds()
    {
        return $this->hasMany(UserRounds::class, 'round_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'student_rounds',
            'round_id',
            'student_id'
        )->withPivot(['payment_id', 'day', 'time', 'status', 'end_date', 'created_at']);
    }
    // protected static function newFactory(): RoundsFactory
    // {
    //     // return RoundsFactory::new();
    // }
}
