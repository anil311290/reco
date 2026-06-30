<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Company Registration</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2>New Company Registration - Pending Approval</h2>
        
        <p>A new company has registered and is awaiting administrator approval:</p>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f9f9f9;"><strong>User Name</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $userName }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f9f9f9;"><strong>User Email</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $userEmail }}</td>
            </tr>
            @if($companyName)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f9f9f9;"><strong>Company Name</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $companyName }}</td>
            </tr>
            @endif
            @if($companyEmail)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f9f9f9;"><strong>Company Email</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $companyEmail }}</td>
            </tr>
            @endif
        </table>
        
        <p style="margin-top: 20px;">Please review and approve this registration in the admin panel.</p>
    </div>
</body>
</html>