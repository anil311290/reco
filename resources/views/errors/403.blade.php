<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 – Access Denied | {{ config('app.name', 'Reco') }}</title>
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fc; }
        .error-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: #fff; border-radius: 16px; padding: 48px 40px; text-align: center; max-width: 460px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-icon { width: 80px; height: 80px; background: #fff3f3; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        .error-icon i { font-size: 36px; color: #ea5455; }
        .error-code { font-size: 64px; font-weight: 700; color: #ea5455; line-height: 1; margin-bottom: 8px; }
        .error-title { font-size: 20px; font-weight: 600; color: #2d2d2d; margin-bottom: 10px; }
        .error-message { font-size: 14px; color: #6e6b7b; margin-bottom: 28px; line-height: 1.6; }
        .btn-home { background: #1f6feb; color: #fff; border: none; padding: 10px 28px; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; transition: background .2s; }
        .btn-home:hover { background: #195ec9; color: #fff; }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="error-code">403</div>
            <div class="error-title">Access Denied</div>
            <p class="error-message">
                {{ $exception->getMessage() ?: 'You do not have permission to perform this action.' }}
            </p>
            <a href="{{ route('admin.dashboard') }}" class="btn-home">
                <i class="bi bi-house me-1"></i> Go to Dashboard
            </a>
            &nbsp;
            <a href="{{ route('admin.login') }}" class="btn-home" style="background:#6e6b7b;">
                <i class="bi bi-box-arrow-right me-1"></i> Login
            </a>
        </div>
    </div>
</body>
</html>
