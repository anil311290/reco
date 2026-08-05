<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\DatabaseBackupService;

class BackupLinkController extends Controller
{
    public function __construct(protected DatabaseBackupService $databaseBackupService)
    {
    }

    public function download(Company $company)
    {
        return $this->databaseBackupService->streamSqlDownload($company);
    }
}
