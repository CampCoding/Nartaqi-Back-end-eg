<?php

namespace Modules\Favourite\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Favourite\Http\Requests\FavouriteRequest;
use Modules\Favourite\Models\Favourite;

class FavouriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function user_favourites(Request $request)
    {
        $student = $request->user();
        if (!$student) {
            return res_data('الطالب غير موجود', 'failed', 404);
        }

        // Get the user's favourite rounds with all required relationships and counts
        $FavouriteItems = Favourite::where('student_id', $student->id)
            ->with([
                'round' => function ($query) {
                    $query->with(['teacher', 'category_parts:id,name'])
                        ->withCount('userRounds as students_count')
                        ->selectRaw('rounds.*, (
                            SELECT COUNT(*)
                            FROM lessons
                            INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
                            WHERE round_contents.round_id = rounds.id
                        ) as lessons_count');
                }
            ])
            ->get();

        // Enhance each round object with category_parts_name
        $FavouriteItems->each(function ($favourite) {
            if ($favourite->round && $favourite->round->category_parts) {
                $favourite->round->category_parts_name = $favourite->round->category_parts->name;
            }
        });

        return res_data($FavouriteItems, 'success', 200);
    }


    public function toggle_favourite(FavouriteRequest $request)
    {
        $student = $request->user();
        if (!$student) {
            return res_data('الطالب غير موجود', 'failed', 404);
        }


        $round_id = $request->round_id;
        if (!$round_id) {
            return res_data('معرف الدورة مطلوب', 'failed', 400);
        }

        $item = Favourite::where('round_id', $round_id)
            ->where('student_id', $student->id)
            ->first();

        if ($item) {
            $item->delete();
            return res_data('تم ازالة الدورة من المفضلة', 'success', 200, $student->id);
        } else {
            $newItem = Favourite::create([
                'student_id' => $student->id,
                'round_id' => $request->round_id,
            ]);
            return res_data('تم إضافة الدورة الى المفضلة', 'success', 201, $student->id);
        }
    }
}
