<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;
use Illuminate\Support\Facades\DB;

class SearchLogModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'search_logs';

    public $timestamps = false;
    protected $fillable = [
        'search_query',
        'search_count',
    ];

    /**
     * Log a search query (increment count if exists, create new if not)
     * 
     * @param string $query The search query string
     * @return void
     */
    public static function logSearch(string $query): void
    {
        // Trim and normalize the search query
        $query = trim($query);

        if (empty($query)) {
            return;
        }

        // Check if the search query already exists
        $existingLog = self::where('search_query', $query)->first();

        if ($existingLog) {
            // Increment the count if it exists
            $existingLog->increment('search_count');
        } else {
            // Create new record if it doesn't exist
            self::create([
                'search_query' => $query,
                'search_count' => 1,
            ]);
        }
    }

    /**
     * Get the most common search queries
     * 
     * @param int $limit Number of results to return (default: 10)
     * @return \Illuminate\Support\Collection
     */
    public static function getMostCommon(int $limit = 10)
    {
        return self::orderBy('search_count', 'desc')
            ->limit($limit)
            ->get(['search_query', 'search_count']);
    }
}
