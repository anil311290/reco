<?php

/**
 * Live HTTP smoke against running artisan serve (real MySQL).
 * Usage: php scripts/smoke_admin_pages.php
 */

$base = getenv('APP_URL') ?: 'http://127.0.0.1:8000';
$email = getenv('SMOKE_EMAIL') ?: 'superadmin@reco.app';
$password = getenv('SMOKE_PASSWORD') ?: '12345678';
$cookieJar = tempnam(sys_get_temp_dir(), 'smoke_cookies');

function req(string $method, string $url, string $cookieJar, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $opts['headers'] ?? [],
        CURLOPT_POSTFIELDS => $opts['body'] ?? null,
    ]);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['status' => 0, 'body' => $error, 'headers' => ''];
    }

    $parts = explode("\r\n\r\n", (string) $raw, 2);
    return [
        'status' => $status,
        'headers' => $parts[0] ?? '',
        'body' => $parts[1] ?? '',
    ];
}

function extractCsrf(string $html): ?string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return null;
}

echo "Smoke testing {$base}\n";

$loginPage = req('GET', "{$base}/admin/login", $cookieJar);
if ($loginPage['status'] !== 200) {
    fwrite(STDERR, "FAIL login page => {$loginPage['status']}\n");
    exit(1);
}
$token = extractCsrf($loginPage['body']);
if (!$token) {
    fwrite(STDERR, "FAIL could not find CSRF token on login page\n");
    exit(1);
}

$login = req('POST', "{$base}/admin/login", $cookieJar, [
    'headers' => ['Content-Type: application/x-www-form-urlencoded'],
    'body' => http_build_query([
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]),
]);

if (!in_array($login['status'], [200, 302, 303], true)) {
    fwrite(STDERR, "FAIL login => {$login['status']}\n{$login['body']}\n");
    exit(1);
}

if ($login['status'] === 200 && stripos($login['body'], 'Login successful') === false && stripos($login['body'], 'dashboard') === false) {
    fwrite(STDERR, "FAIL login body not successful\n{$login['body']}\n");
    exit(1);
}

$paths = [
    '/admin/dashboard',
    '/admin/accounts',
    '/admin/accounts/create',
    '/admin/parties',
    '/admin/parties/create',
    '/admin/items',
    '/admin/items/create',
    '/admin/item-categories',
    '/admin/tax-rates',
    '/admin/vouchers',
    '/admin/vouchers/create/payment',
    '/admin/vouchers/create/receipt',
    '/admin/vouchers/create/journal',
    '/admin/sales-invoices',
    '/admin/sales-invoices/create',
    '/admin/purchase-invoices',
    '/admin/purchase-invoices/create',
    '/admin/financial-years',
    '/admin/settings',
    '/admin/reports',
    '/admin/reports/day-book',
    '/admin/reports/ledger',
    '/admin/reports/trial-balance',
    '/admin/reports/profit-loss',
    '/admin/reports/receipt-payment',
    '/admin/reports/balance-sheet',
    '/admin/reports/debtors-outstanding',
    '/admin/reports/creditors-outstanding',
    '/admin/reports/cash-flow', // legacy redirect
    '/admin/audit-logs',
    '/',
];

$ok = 0;
$fail = [];

foreach ($paths as $path) {
    $res = req('GET', "{$base}{$path}", $cookieJar);
    $status = $res['status'];
    $okStatuses = in_array($status, [200, 301, 302, 303], true);

    // Redirect chain for legacy cash-flow
    if ($path === '/admin/reports/cash-flow') {
        $okStatuses = in_array($status, [301, 302, 303], true);
    }

    if ($okStatuses) {
        $ok++;
        echo "OK  {$status}  {$path}\n";
    } else {
        $snippet = substr(preg_replace('/\s+/', ' ', strip_tags($res['body'])), 0, 180);
        $fail[] = "{$path} => {$status} {$snippet}";
        echo "FAIL {$status}  {$path}\n";
    }
}

@unlink($cookieJar);

echo "\nPassed: {$ok}/" . count($paths) . "\n";
if ($fail) {
    echo "Failures:\n- " . implode("\n- ", $fail) . "\n";
    exit(1);
}

echo "All smoke checks passed.\n";
exit(0);
