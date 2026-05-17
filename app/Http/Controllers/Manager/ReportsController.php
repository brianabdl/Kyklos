<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SavedReport;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        $reports = SavedReport::where('org_id', $request->org_id)
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['reports' => $reports]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'reportType'     => 'required|in:payroll,overtime,late,attendance,custom',
            'dateRangeStart' => 'required|date',
            'dateRangeEnd'   => 'required|date|after_or_equal:dateRangeStart',
            'scheduleCron'   => 'nullable|string',
        ]);

        $report = SavedReport::create([
            'org_id'           => $request->org_id,
            'created_by'       => $request->user()->id,
            'title'            => $request->title,
            'report_type'      => $request->reportType,
            'date_range_start' => $request->dateRangeStart,
            'date_range_end'   => $request->dateRangeEnd,
            'schedule_cron'    => $request->scheduleCron,
        ]);

        return response()->json(['report' => $report], 201);
    }

    public function run(Request $request, string $id): StreamedResponse
    {
        $report = SavedReport::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        $rows   = $this->reports->generate($report);

        $report->update(['last_run_at' => now()]);

        return response()->streamDownload(function () use ($rows) {
            if (empty($rows)) {
                return;
            }
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "kyklos-report-{$report->id}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\Response
    {
        SavedReport::where('id', $id)->where('org_id', $request->org_id)->firstOrFail()->delete();
        return response()->noContent();
    }
}
