<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadReportRequest;
use App\Services\ReportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function download(DownloadReportRequest $request, ReportService $reportService): StreamedResponse
    {
        $restaurant = $request->user()->restaurant;
        $from = Carbon::parse($request->validated('date_from'));
        $to = Carbon::parse($request->validated('date_to'));

        $filename = sprintf(
            'sales-report-%s-to-%s.csv',
            $from->toDateString(),
            $to->toDateString(),
        );

        return response()->streamDownload(
            fn () => $reportService->writeSalesReportCsv('php://output', $restaurant, $from, $to),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
