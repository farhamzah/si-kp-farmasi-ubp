<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserImportBatch;
use App\Services\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserImportController extends Controller
{
    public function __construct(private readonly UserImportService $service) {}

    public function index(): View
    {
        return view('admin.imports.index', [
            'types' => UserImportService::TYPES,
            'preview' => session('import_preview'),
            'importRows' => session('import_rows'),
            'importType' => session('import_type', 'mahasiswa'),
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import_type' => ['required', Rule::in(UserImportService::TYPES)],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'File import wajib diunggah.',
            'file.mimes' => 'File harus berformat xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $rows = $this->service->parseFile($validated['file']);
        $preview = $this->service->preview($validated['import_type'], $rows);

        Session::put('import_rows', $rows);
        Session::put('import_preview', $preview);
        Session::put('import_type', $validated['import_type']);
        Session::put('import_filename', $validated['file']->getClientOriginalName());

        return redirect()->route('admin.import-users.index')->with('status', 'Preview import berhasil dibuat.');
    }

    public function process(Request $request): RedirectResponse
    {
        $rows = session('import_rows', []);
        $type = session('import_type');

        if (! $type || empty($rows)) {
            return redirect()->route('admin.import-users.index')->withErrors(['file' => 'Silakan upload file dan preview terlebih dahulu.']);
        }

        $batch = $this->service->process($type, $rows, $request->user(), session('import_filename'));

        Session::forget(['import_rows', 'import_preview', 'import_type', 'import_filename']);

        return redirect()->route('admin.import-users.history.show', $batch)->with('status', 'Import user selesai diproses.');
    }

    public function history(): View
    {
        return view('admin.imports.history', [
            'batches' => UserImportBatch::with('importedBy')->latest()->paginate(10),
        ]);
    }

    public function show(UserImportBatch $batch): View
    {
        return view('admin.imports.show', [
            'batch' => $batch->load(['importedBy', 'errors']),
        ]);
    }

    public function template(string $type): BinaryFileResponse
    {
        abort_unless(in_array($type, UserImportService::TYPES, true), 404);

        $filename = 'template_import_'.$type.'.xlsx';

        return Excel::download($this->service->templateExport($type), $filename);
    }
}
