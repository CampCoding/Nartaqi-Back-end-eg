<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * An upload that failed, with the reason in Arabic (message) and a stable code (reason) the frontend can check.
 * Rendered by Laravel as JSON wherever it is thrown.
 */
class UploadFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason,
        public readonly int $status = 500,
        public readonly ?string $field = null,
        public readonly ?string $fileName = null,
    ) {
        parent::__construct($message);
    }

    public function render($request = null): JsonResponse
    {
        return response()->json(array_filter([
            'statusCode' => $this->status,
            'status' => 'failed',
            'message' => $this->getMessage(),
            'reason' => $this->reason,
            'field' => $this->field,
            'file_name' => $this->fileName,
        ], fn ($value) => $value !== null), $this->status);
    }

    /** Server-side problems go to the log; a user's too-big or interrupted upload does not. */
    public function report(): bool
    {
        if ($this->status >= 500) {
            Log::error("Upload failed [{$this->reason}]: {$this->getMessage()}", array_filter([
                'field' => $this->field,
                'file' => $this->fileName,
            ]));
        }

        return true;
    }
}
