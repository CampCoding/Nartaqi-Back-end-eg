<?php

use Carbon\Carbon;

if(!function_exists('res_data')){
    function res_data($data,$message='',$status=200){
        return response([
            'statusCode'=>$status,
            'status'=>in_array($status,[200,201,202])?'success':'failed',
            'message'=>$data
        ],$status);
    }
    }

    if(!function_exists('findElement')){
        function findElement($id,$modelName){
            $element=$modelName::find($id);
            if($element){
                return 'founded';
            }
            else return "not_found";
        }
    }


if(!function_exists('myResponse')){
    function myResponse($message,$status){
        return response()->json([
            'message'=>$message,
            'status'=>in_array($status,[200,201,202])?'success':'failed',
            'statusCode'=>200,
        ]);
    }
}

if (!function_exists('formatMinutesRemaining')) {
    function formatMinutesRemaining(Carbon $expiresAt): string
    {
        $diff = now()->diffInMinutes($expiresAt, false);

        if ($diff <= 0) {
            return 'الرمز منتهي';
        }

        if ($diff === 1) {
            return "بعد دقيقة واحدة";
        } elseif ($diff < 10) {
            return "بعد {$diff} دقائق";
        }

        return "بعد حوالي {$diff} دقيقة";
    }
}
