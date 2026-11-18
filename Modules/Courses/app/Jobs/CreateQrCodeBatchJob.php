<?php

namespace Modules\Courses\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Courses\Services\QrCodeBatchService;

/**
 * Big QR batches are created here instead of inside the request. With QUEUE_CONNECTION=sync it
 * simply runs inline.
 */
class CreateQrCodeBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $sectionId, public int $count)
    {
    }

    public function handle(QrCodeBatchService $service): void
    {
        $service->createBatch($this->sectionId, $this->count);
    }
}
