<?php

namespace App\Console\Commands;

use App\Mail\BackupDownloadLinkMail;
use App\Models\Company;
use App\Models\Setting;
use App\Services\DatabaseBackupService;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledBackupLinks extends Command
{
    protected $signature = 'backup:send-links';

    protected $description = 'Send scheduled backup download links to configured company emails';

    public function __construct(
        protected SettingsService $settingsService,
        protected DatabaseBackupService $databaseBackupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $companyIds = Setting::query()
            ->where('key', 'backup.schedule_enabled')
            ->whereIn('value', ['1', 'true', 'yes', 'on'])
            ->whereNotNull('company_id')
            ->pluck('company_id')
            ->unique()
            ->values();

        if ($companyIds->isEmpty()) {
            $this->line('No companies have backup auto-mail enabled.');
            return self::SUCCESS;
        }

        $sent = 0;
        $now = CarbonImmutable::now();

        foreach ($companyIds as $companyId) {
            /** @var Company|null $company */
            $company = Company::query()->find($companyId);
            if (!$company) {
                continue;
            }

            $backupSettings = $this->settingsService->getBackupSettings((int) $companyId);
            if (!$this->settingsService->shouldSendBackupLinkNow($backupSettings, $now)) {
                continue;
            }

            $toEmail = trim((string) ($backupSettings['schedule_email'] ?? ''));
            if ($toEmail === '') {
                continue;
            }

            $expiryMinutes = $this->settingsService->calculateBackupLinkExpiryMinutes($backupSettings);
            $downloadUrl = $this->databaseBackupService->buildTemporaryDownloadLink((int) $companyId, $expiryMinutes);

            Mail::to($toEmail)->send(new BackupDownloadLinkMail(
                companyName: $company->name,
                downloadUrl: $downloadUrl,
                expiresInMinutes: $expiryMinutes,
            ));

            $this->settingsService->markBackupLinkSent((int) $companyId, $now);
            $sent++;
        }

        $this->info("Sent {$sent} scheduled backup email(s).");

        return self::SUCCESS;
    }
}
