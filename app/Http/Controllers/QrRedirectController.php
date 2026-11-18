<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Courses\Models\QrCodeModel;
use Modules\Courses\Models\QrScanModel;

/**
 * Public target of every printed book QR: {qr.base_url}/{serial}. Looks the serial up and redirects
 * (302, never 301 so browsers don't remember it) to whatever target_url is set now.
 */
class QrRedirectController extends Controller
{
    public function __invoke(Request $request, string $serial)
    {
        $serial = strtoupper(trim($serial));

        if (!preg_match('/^[A-Z]{1,5}\d{4,16}$/', $serial)) {
            return $this->page('qr.invalid', 404);
        }

        // ['id' => .., 'active' => .., 'target' => ..] or ['id' => null] for an unknown serial.
        $code = Cache::store('file')->remember(
            QrCodeModel::cacheKey($serial),
            config('qr.cache_ttl'),
            function () use ($serial) {
                $row = QrCodeModel::where('serial', $serial)->first(['id', 'is_active', 'target_url']);

                return $row
                    ? ['id' => $row->id, 'active' => (bool) $row->is_active, 'target' => $row->target_url]
                    : ['id' => null];
            }
        );

        if (!$code['id']) {
            return $this->page('qr.invalid', 404);
        }

        $this->logScanAfterResponse($code['id'], $request->userAgent());

        if (!$code['active'] || blank($code['target'])) {
            return $this->page('qr.unavailable', 200);
        }

        return redirect()->away($code['target'], 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function page(string $view, int $status)
    {
        return response()->view($view, [], $status)->header('Cache-Control', 'no-store');
    }

    /**
     * Counting the scan must not slow the redirect down, so it is written after the response is sent.
     */
    private function logScanAfterResponse(int $codeId, ?string $userAgent): void
    {
        app()->terminating(function () use ($codeId, $userAgent) {
            try {
                QrScanModel::create([
                    'qr_code_id' => $codeId,
                    'scanned_at' => now(),
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('QR scan log failed: ' . $e->getMessage());
            }
        });
    }
}
