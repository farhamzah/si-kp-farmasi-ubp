<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreKpDocumentRequirementRequest;
use App\Http\Requests\Management\UpdateKpDocumentRequirementRequest;
use App\Models\KpDocumentRequirement;
use App\Models\KpPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KpDocumentRequirementController extends Controller
{
    public function index(Request $request): View
    {
        $requirements = KpDocumentRequirement::query()
            ->with('period')
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->period))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->orderBy('kp_period_id')
            ->orderBy('sort_order')
            ->paginate(10)
            ->withQueryString();

        return view('management.document-requirements.index', [
            'requirements' => $requirements,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('management.document-requirements.create', [
            'requirement' => new KpDocumentRequirement(['is_required' => true, 'allowed_file_types' => 'pdf,jpg,jpeg,png', 'max_file_size_mb' => 5, 'status' => 'aktif']),
            'periods' => KpPeriod::latest()->get(),
        ]);
    }

    public function store(StoreKpDocumentRequirementRequest $request): RedirectResponse
    {
        KpDocumentRequirement::create($request->validated() + [
            'is_required' => $request->boolean('is_required'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('management.document-requirements.index')->with('status', 'Persyaratan dokumen berhasil dibuat.');
    }

    public function edit(KpDocumentRequirement $requirement): View
    {
        return view('management.document-requirements.edit', [
            'requirement' => $requirement,
            'periods' => KpPeriod::latest()->get(),
        ]);
    }

    public function update(UpdateKpDocumentRequirementRequest $request, KpDocumentRequirement $requirement): RedirectResponse
    {
        $requirement->update($request->validated() + [
            'is_required' => $request->boolean('is_required'),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('management.document-requirements.index')->with('status', 'Persyaratan dokumen berhasil diperbarui.');
    }

    public function destroy(KpDocumentRequirement $requirement): RedirectResponse
    {
        if ($requirement->documents()->whereNotNull('file_path')->exists()) {
            return back()->withErrors(['requirement' => 'Persyaratan sudah digunakan, nonaktifkan jika tidak dipakai.']);
        }

        $requirement->delete();

        return back()->with('status', 'Persyaratan dokumen berhasil dihapus.');
    }
}
