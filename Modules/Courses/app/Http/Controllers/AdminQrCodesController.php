<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Jobs\CreateQrCodeBatchJob;
use Modules\Courses\Models\QrBookModel;
use Modules\Courses\Models\QrCodeModel;
use Modules\Courses\Models\QrSectionModel;
use Modules\Courses\Services\QrCodeBatchService;

/**
 * Admin side of the dynamic book QR codes. The QR image is built by the dashboard from each code's
 * qr_url ({qr.base_url}/{serial}); target_url is only where /q/{serial} redirects to.
 */
class AdminQrCodesController extends Controller
{
    /**
     * Create a batch of codes for a section. Either an existing qr_section_id (to add more codes to
     * it) or book_title + section_title (created if they don't exist yet).
     */
    public function createBatch(Request $request, QrCodeBatchService $service)
    {
        $data = $request->validate([
            'qr_section_id' => 'nullable|exists:qr_sections,id',
            'book_title' => 'required_without:qr_section_id|nullable|string|max:255',
            'section_title' => 'required_without:qr_section_id|nullable|string|max:255',
            'count' => 'nullable|integer|min:1|max:' . config('qr.max_batch'),
        ]);

        $count = (int) ($data['count'] ?? 500);

        if (!empty($data['qr_section_id'])) {
            $section = QrSectionModel::find($data['qr_section_id']);
        } else {
            $book = QrBookModel::firstOrCreate(['title' => trim($data['book_title'])]);
            $section = QrSectionModel::firstOrCreate(['qr_book_id' => $book->id, 'title' => trim($data['section_title'])]);
        }

        $queued = $count > config('qr.queue_threshold') && config('queue.default') !== 'sync';

        if ($queued) {
            CreateQrCodeBatchJob::dispatch($section->id, $count);
        } else {
            $service->createBatch($section->id, $count);
        }

        $section->load('book')->loadCount('codes');

        return res_data([
            'section' => $section,
            'requested_count' => $count,
            'queued' => $queued,
            'codes_count' => $section->codes_count,
        ], $queued ? 'جاري إنشاء الأكواد في الخلفية' : 'تم إنشاء الأكواد بنجاح', 201);
    }

    /**
     * Books with their sections and how many codes each section has.
     */
    public function getBooks(Request $request)
    {
        $books = QrBookModel::with(['sections' => fn ($q) => $q->withCount([
            'codes',
            'codes as codes_with_url_count' => fn ($c) => $c->whereNotNull('target_url')->where('target_url', '!=', ''),
        ])->orderBy('id')])
            ->orderByDesc('id')
            ->get();

        return res_data($books, 'success', 200);
    }

    public function updateBook(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:qr_books,id',
            'title' => 'required|string|max:255',
        ]);

        $book = QrBookModel::find($data['id']);
        $book->update(['title' => $data['title']]);

        return res_data($book, 'تم تعديل الكتاب بنجاح', 200);
    }

    public function updateSection(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:qr_sections,id',
            'title' => 'required|string|max:255',
        ]);

        $section = QrSectionModel::find($data['id']);
        $section->update(['title' => $data['title']]);

        return res_data($section, 'تم تعديل القسم بنجاح', 200);
    }

    /**
     * Paginated codes with filters. status: with_url | without_url | inactive.
     */
    public function getCodes(Request $request)
    {
        $data = $request->validate([
            'qr_section_id' => 'nullable|exists:qr_sections,id',
            'qr_book_id' => 'nullable|exists:qr_books,id',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:with_url,without_url,inactive',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        $like = filled($data['search'] ?? null) ? '%' . addcslashes(trim($data['search']), '\\%_') . '%' : null;

        $codes = QrCodeModel::query()
            ->withCount('scans')
            ->when($data['qr_section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            ->when($data['qr_book_id'] ?? null, fn ($q, $id) => $q->whereIn('section_id', QrSectionModel::where('qr_book_id', $id)->select('id')))
            ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('serial', 'like', $like)->orWhere('name', 'like', $like)->orWhere('notes', 'like', $like)))
            ->when(($data['status'] ?? null) === 'with_url', fn ($q) => $q->whereNotNull('target_url')->where('target_url', '!=', ''))
            ->when(($data['status'] ?? null) === 'without_url', fn ($q) => $q->where(fn ($w) => $w->whereNull('target_url')->orWhere('target_url', '')))
            ->when(($data['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('section_id')
            ->orderBy('id')
            ->paginate((int) ($data['per_page'] ?? 50));

        return res_data($codes, 'success', 200);
    }

    /**
     * One code by id or serial, with its section and book.
     */
    public function getCode(Request $request)
    {
        $data = $request->validate([
            'id' => 'required_without:serial|nullable|integer',
            'serial' => 'required_without:id|nullable|string|max:32',
        ]);

        $code = QrCodeModel::with('section.book')
            ->withCount('scans')
            ->when(!empty($data['id']), fn ($q) => $q->where('id', $data['id']), fn ($q) => $q->where('serial', strtoupper(trim($data['serial']))))
            ->first();

        if (!$code) {
            return res_data('الكود غير موجود', 'not found', 404);
        }

        return res_data($code, 'success', 200);
    }

    /**
     * Edit a code: name is required, target_url and notes are optional. The serial (and so the
     * printed QR) never changes.
     */
    public function updateCode(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:qr_codes,id',
            'name' => 'required|string|max:255',
            'target_url' => 'nullable|url:http,https|max:2000',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        // Only fields that were sent change; sending target_url/notes empty clears them.
        $changes = ['name' => $data['name']];
        if ($request->exists('target_url')) {
            $changes['target_url'] = filled($data['target_url'] ?? null) ? trim($data['target_url']) : null;
        }
        if ($request->exists('notes')) {
            $changes['notes'] = filled($data['notes'] ?? null) ? $data['notes'] : null;
        }
        if (isset($data['is_active'])) {
            $changes['is_active'] = (bool) $data['is_active'];
        }

        $code = QrCodeModel::find($data['id']);
        $code->update($changes);

        // /q/{serial} caches the lookup; drop it so the new link works right away.
        QrCodeModel::forgetCached($code->serial);

        return res_data($code->fresh('section.book'), 'تم تعديل الكود بنجاح', 200);
    }

    /**
     * Turn codes on or off in bulk (e.g. a whole section).
     */
    public function setActive(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
            'is_active' => 'required|boolean',
        ]);

        $codes = QrCodeModel::whereIn('id', $data['ids'])->get(['id', 'serial']);
        QrCodeModel::whereIn('id', $codes->pluck('id'))->update(['is_active' => (bool) $data['is_active']]);
        $codes->each(fn ($c) => QrCodeModel::forgetCached($c->serial));

        return res_data(['updated_count' => $codes->count()], 'تم التحديث بنجاح', 200);
    }

    /**
     * Every code of a section (not paginated) for downloading the QR images as a ZIP or a PDF.
     */
    public function exportSection(Request $request)
    {
        $data = $request->validate([
            'qr_section_id' => 'required|exists:qr_sections,id',
        ]);

        $section = QrSectionModel::with('book')->find($data['qr_section_id']);
        $codes = QrCodeModel::where('section_id', $section->id)
            ->orderBy('id')
            ->get(['id', 'serial', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'serial' => $c->serial, 'name' => $c->name, 'qr_url' => $c->qr_url]);

        return res_data([
            'book' => ['id' => $section->book->id, 'title' => $section->book->title],
            'section' => ['id' => $section->id, 'title' => $section->title],
            'codes_count' => $codes->count(),
            'codes' => $codes,
        ], 'success', 200);
    }
}
