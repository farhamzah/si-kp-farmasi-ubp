<?php

namespace App\Http\Controllers\Management;

use App\Exports\KpTableReportExport;
use App\Http\Controllers\Controller;
use App\Services\KpInternalSupervisorWorkloadReportService;
use App\Support\SimplePdfReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InternalSupervisorWorkloadReportController extends Controller
{
    public function index(Request $request, KpInternalSupervisorWorkloadReportService $report): View
    {
        return view('management.assignments.internal-supervisor-workload', [
            'rows' => $report->rows($request),
            'filters' => $request->only(['q', 'period', 'status']),
            'periods' => $report->periods(),
            'statusOptions' => $report->statusOptions(),
            'filterSummary' => $report->filterSummary($request),
        ]);
    }

    public function preview(Request $request, KpInternalSupervisorWorkloadReportService $report): View
    {
        return view('management.assignments.internal-supervisor-workload-preview', [
            'rows' => $report->rows($request),
            'filters' => $report->filterSummary($request),
            'printMode' => $request->boolean('print'),
        ]);
    }

    public function download(string $format, Request $request, KpInternalSupervisorWorkloadReportService $report): Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['word', 'excel', 'pdf'], true), 404);

        $rows = $report->rows($request);
        $filters = $report->filterSummary($request);
        $exportMeta = $filters + ['Total data' => max(0, $rows->count() - 1)];
        $filename = 'rekap-pembimbing-dalam-'.now()->format('Ymd-His');

        if ($format === 'excel') {
            return Excel::download(new KpTableReportExport('Rekap Pembimbing Dalam', $exportMeta, $rows), $filename.'.xlsx');
        }

        if ($format === 'pdf') {
            $headings = array_keys($rows->first() ?? [
                'No' => '',
                'Nama Dosen Pembimbing' => '',
                'RS' => '',
                'Apotek' => '',
                'Industri' => '',
                'Lainnya' => '',
                'Total' => '',
            ]);

            return response(SimplePdfReport::table(
                'Rekap Pembimbing Dalam',
                $exportMeta,
                $headings,
                $rows->map(fn ($row) => array_values($row))->all()
            ), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            ]);
        }

        return response()
            ->view('management.assignments.internal-supervisor-workload-word', [
                'rows' => $rows,
                'filters' => $filters,
            ], 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.doc"',
            ]);
    }
}
