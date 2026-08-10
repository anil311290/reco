<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Backup Link</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 8px;">Database Backup Link</h2>
    <p style="margin-top: 0;">Hello,</p>
    <p>
        Your scheduled backup link for <strong>{{ $companyName }}</strong> is ready.
        This link will expire in <strong>{{ $expiresInMinutes }} minutes</strong>.
    </p>
    <p style="margin: 20px 0;">
        <a href="{{ $downloadUrl }}" style="display: inline-block; padding: 10px 16px; background: #1f6feb; color: #ffffff; text-decoration: none; border-radius: 6px;">Download Backup</a>
    </p>
    <p>If the button does not work, copy and paste this URL:</p>
    <p style="word-break: break-all;">{{ $downloadUrl }}</p>
    <p style="margin-top: 20px; color: #6b7280; font-size: 12px;">This is an auto-generated email from Reco.</p>
</body>
</html>
