<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\DataImportLog;
use App\Services\Aims\AimsCsvImportService;
use App\Services\ModuleCatalogService;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AimsImportController extends Controller
{
    public function __construct(
        private readonly ModuleCatalogService $catalog,
    ) {}

    public function logs(Request $request)
    {
        $this->authorizeImport($request);

        $logs = DataImportLog::query()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 20), 50));

        return response()->json($logs);
    }

    public function importStudents(Request $request, AimsCsvImportService $service)
    {
        return $this->runImport($request, $service, 'students');
    }

    public function importAttendance(Request $request, AimsCsvImportService $service)
    {
        return $this->runImport($request, $service, 'attendance');
    }

    public function importFeeVouchers(Request $request, AimsCsvImportService $service, NotificationDispatchService $dispatchService)
    {
        return $this->runImport($request, $service, 'fee_vouchers', $dispatchService);
    }

    public function importFeeDeposits(Request $request, AimsCsvImportService $service)
    {
        return $this->runImport($request, $service, 'fee_deposits');
    }

    public function importTestResults(Request $request, AimsCsvImportService $service)
    {
        return $this->runImport($request, $service, 'test_results');
    }

    public function importExamResults(Request $request, AimsCsvImportService $service)
    {
        return $this->runImport($request, $service, 'exam_results');
    }

    private function runImport(
        Request $request,
        AimsCsvImportService $service,
        string $dataType,
        ?NotificationDispatchService $dispatchService = null,
    ) {
        $this->authorizeImport($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $stored = $data['file']->store('aims-imports');
        $path = Storage::path($stored);

        try {
            $stats = $service->import($dataType, $path, (int) $request->user()->id, $dispatchService);
            $log = $service->log((int) $request->user()->id, $dataType, $data['file']->getClientOriginalName(), $stats);

            activity('AIMS Import')
                ->causedBy($request->user())
                ->withProperties([
                    'data_type' => $dataType,
                    'filename' => $data['file']->getClientOriginalName(),
                    'stats' => $stats,
                ])
                ->log("Imported {$dataType} from AIMS CSV");

            return response()->json([
                'message' => 'Import completed',
                'stats' => $stats,
                'log_id' => $log->id,
            ]);
        } finally {
            Storage::delete($stored);
        }
    }

    private function authorizeImport(Request $request): void
    {
        abort_unless(
            $this->catalog->userCanAccessModule($request->user(), 'aims-import', 'web'),
            403
        );
    }
}
