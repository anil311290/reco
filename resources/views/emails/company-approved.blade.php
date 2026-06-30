<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Company Registration Approved</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2>Your Company Registration is Approved!</h2>
        
        <p>Congratulations! Your company <strong>{{ $companyName }}</strong> has been approved.</p>
        
        <p>You can now log in to your account and start using Reco accounting software.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" 
               style="background: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">
                Login to Your Account
            </a>
        </div>
        
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>