<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function buildBackupFilename(?Company $company = null): string
    {
        $slug = $company?->slug ?: 'company';
        $timestamp = now()->format('Ymd_His');

        return sprintf('%s_backup_%s.sql', $slug, $timestamp);
    }

    public function streamSqlDownload(?Company $company = null)
    {
        $connection = $this->getDatabaseConnectionConfig();
        $filename = $this->buildBackupFilename($company);

        if (($connection['driver'] ?? '') !== 'mysql') {
            throw new RuntimeException('Backup download is supported only for MySQL connections.');
        }

        $command = $this->buildMysqlDumpCommand($connection);
        $env = $this->buildMySqlEnv($connection);

        return response()->streamDownload(function () use ($command, $env): void {
            $stderr = '';

            $process = new Process($command, null, $env);
            $process->setTimeout(0);
            $process->setIdleTimeout(0);
            $process->run(function (string $type, string $buffer) use (&$stderr): void {
                if ($type === Process::OUT) {
                    echo $buffer;
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    return;
                }

                $stderr .= $buffer;
            });

            if (!$process->isSuccessful()) {
                throw new RuntimeException('Backup generation failed. ' . trim($stderr));
            }
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restoreFromSqlUpload(UploadedFile $file): void
    {
        $connection = $this->getDatabaseConnectionConfig();

        if (($connection['driver'] ?? '') !== 'mysql') {
            throw new RuntimeException('Backup restore is supported only for MySQL connections.');
        }

        $command = $this->buildMysqlRestoreCommand($connection);
        $env = $this->buildMySqlEnv($connection);
        $stderr = '';

        $process = new Process($command, null, $env);
        $process->setTimeout(0);
        $process->setIdleTimeout(0);
        $process->setInput(fopen($file->getRealPath(), 'rb'));
        $process->run(function (string $type, string $buffer) use (&$stderr): void {
            if ($type === Process::ERR) {
                $stderr .= $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Restore failed. ' . trim($stderr));
        }
    }

    public function buildTemporaryDownloadLink(int $companyId, int $expiresInMinutes): string
    {
        $expiresAt = now()->addMinutes($expiresInMinutes);

        return URL::temporarySignedRoute(
            'backup.signed-download',
            $expiresAt,
            ['company' => $companyId]
        );
    }

    protected function getDatabaseConnectionConfig(): array
    {
        $connectionName = config('database.default', 'mysql');

        return array_merge(
            ['driver' => $connectionName],
            config("database.connections.{$connectionName}", [])
        );
    }

    protected function buildMysqlDumpCommand(array $connection): array
    {
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('Database name is missing in connection settings.');
        }

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--events',
            '--set-gtid-purged=OFF',
            '--default-character-set=utf8mb4',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? '3306'),
            '--user=' . (string) ($connection['username'] ?? ''),
            $database,
        ];

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket=' . (string) $connection['unix_socket'];
        }

        return $command;
    }

    protected function buildMysqlRestoreCommand(array $connection): array
    {
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('Database name is missing in connection settings.');
        }

        $command = [
            'mysql',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? '3306'),
            '--user=' . (string) ($connection['username'] ?? ''),
            '--default-character-set=utf8mb4',
            $database,
        ];

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket=' . (string) $connection['unix_socket'];
        }

        return $command;
    }

    protected function buildMySqlEnv(array $connection): array
    {
        $password = (string) ($connection['password'] ?? '');

        if ($password === '') {
            return [];
        }

        return ['MYSQL_PWD' => $password];
    }
}
