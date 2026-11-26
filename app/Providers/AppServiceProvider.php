<?php

namespace App\Providers;

use App\Support\Uploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A file refused for its size or type: the message also says what was actually sent,
        // e.g. "حجم ملف الـ PDF يجب ألا يزيد عن 500 ميجابايت (حجم الملف المرفوع: 612 ميجابايت)".
        // (A custom replacer replaces Laravel's own, so :max / :values are filled here too.)
        Validator::replacer('max', function ($message, $attribute, $rule, $parameters, $validator) {
            return str_replace(':max', $parameters[0] ?? '', $message) . Uploads::sentFileNote($validator, $attribute, 'size');
        });
        foreach (['mimes', 'mimetypes', 'extensions'] as $typeRule) {
            Validator::replacer($typeRule, function ($message, $attribute, $rule, $parameters, $validator) {
                return str_replace(':values', implode(', ', $parameters), $message) . Uploads::sentFileNote($validator, $attribute, 'type');
            });
        }
    }
}
