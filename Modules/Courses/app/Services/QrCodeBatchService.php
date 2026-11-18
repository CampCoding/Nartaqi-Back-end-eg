<?php

namespace Modules\Courses\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\QrCodeModel;
use RuntimeException;

class QrCodeBatchService
{
    private const CHUNK = 500;
    private const MAX_RETRIES = 5;

    /**
     * Create $count codes in a section, inserted in chunks. Each code gets a unique random serial,
     * an empty target_url and the next default name in the section (QR-0001, QR-0002, ...).
     * Returns the number of codes created.
     */
    public function createBatch(int $sectionId, int $count): int
    {
        $created = 0;

        while ($created < $count) {
            $size = min(self::CHUNK, $count - $created);
            $this->insertChunk($sectionId, $size);
            $created += $size;
        }

        return $created;
    }

    private function insertChunk(int $sectionId, int $size): void
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                DB::transaction(function () use ($sectionId, $size) {
                    // Locking the section's rows keeps names sequential if two batches run at once.
                    $next = QrCodeModel::where('section_id', $sectionId)->lockForUpdate()->count() + 1;
                    $now = now();

                    $rows = [];
                    foreach ($this->uniqueSerials($size) as $i => $serial) {
                        $rows[] = [
                            'section_id' => $sectionId,
                            'serial' => $serial,
                            'name' => 'QR-' . str_pad((string) ($next + $i), 4, '0', STR_PAD_LEFT),
                            'target_url' => null,
                            'notes' => null,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    QrCodeModel::insert($rows);
                });

                return;
            } catch (QueryException $e) {
                // A serial taken between the check and the insert hits the unique index: try a fresh chunk.
                if (!$this->isDuplicateKey($e) || $attempt === self::MAX_RETRIES) {
                    throw $e;
                }
            }
        }
    }

    /**
     * $size serials that are unique within the list and not in the database yet.
     */
    public function uniqueSerials(int $size): array
    {
        $serials = [];

        for ($round = 0; count($serials) < $size; $round++) {
            if ($round > 50) {
                throw new RuntimeException('Could not generate unique QR serials; increase qr.serial_digits.');
            }

            $candidates = [];
            while (count($candidates) < $size - count($serials)) {
                $serial = $this->randomSerial();
                if (!isset($serials[$serial])) {
                    $candidates[$serial] = true;
                }
            }

            $taken = QrCodeModel::whereIn('serial', array_keys($candidates))->pluck('serial')->flip();
            foreach ($candidates as $serial => $_) {
                if (!isset($taken[$serial])) {
                    $serials[$serial] = true;
                }
            }
        }

        return array_keys($serials);
    }

    public function randomSerial(): string
    {
        $digits = max(4, (int) config('qr.serial_digits'));
        $number = '';
        for ($i = 0; $i < $digits; $i++) {
            $number .= random_int(0, 9);
        }

        return config('qr.serial_prefix') . $number;
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return in_array($e->errorInfo[1] ?? null, [1062, 19], true) // MySQL / SQLite
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }
}
