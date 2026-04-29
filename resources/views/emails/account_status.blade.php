<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Status</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f7fc; padding: 2rem;">
    <div style="max-width: 550px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
        <h2 style="color: #4f46e5; margin-top: 0;">
            {{ $data['status'] == 'approved' ? '✅ Account Approved!' : '❌ Account Request Update' }}
        </h2>
        <p>Dear {{ $data['full_name'] }},</p>

        @if($data['status'] == 'approved')
            <p>Your account request has been <strong>approved</strong>!</p>
            <p>You may now log in using your institutional email:</p>
            <p style="font-weight: bold; font-size: 1.1rem; margin: 1rem 0;">{{ $data['institutional_email'] }}</p>
        @else
            <p>We regret to inform you that your account request has been <strong>declined</strong>.</p>
            <p>You may submit a new request at a later time if needed.</p>
            <p>If you have any questions, please contact the MIS office.</p>
        @endif

        <p style="margin-bottom: 1.5rem;">Login here:</p>
        <a href="http://127.0.0.1:8000" target="_blank" style="display: inline-block; background: #4f46e5; color: #fff; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">PTC Reservation System</a>
        <p style="margin-top: 2rem; font-size: 0.85rem; color: #777;">PTC MIS Team</p>
    </div>
</body>
</html>