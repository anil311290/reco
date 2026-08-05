<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatabaseBackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $databaseBackupService,
        protected SettingsService $settingsService,
    ) {
    }

    public function download()
    {
        $user = Auth::user();

        return $this->databaseBackupService->streamSqlDownload($user?->company);
    }

    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:sql,txt', 'max:102400'],
        ]);

        try {
            $this->databaseBackupService->restoreFromSqlUpload($request->file('backup_file'));

            return ResponseHelper::success(null, 'Database restored successfully.');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function updateAutomation(Request $request): JsonResponse
    {
        $companyId = (int) Auth::user()->company_id;

        $validated = $request->validate([
            'schedule_enabled' => ['nullable', 'boolean'],
            'schedule_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'schedule_every_value' => ['required', 'integer', 'min:1', 'max:1000'],
            'schedule_every_unit' => ['required', 'in:minutes,hours,days'],
            'link_expiry_value' => ['required', 'integer', 'min:1', 'max:1000'],
            'link_expiry_unit' => ['required', 'in:minutes,hours,days'],
        ]);

        if (!empty($validated['schedule_enabled']) && empty($validated['schedule_email'])) {
            return ResponseHelper::validationError([
                'schedule_email' => ['Schedule email is required when auto backup email is enabled.'],
            ]);
        }

        try {
            $this->settingsService->updateBackupSettings($validated, $companyId);

            return ResponseHelper::success(null, 'Backup automation settings saved successfully.');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
