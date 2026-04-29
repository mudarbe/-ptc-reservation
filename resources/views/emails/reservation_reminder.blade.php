<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reservation Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f7fc; padding: 2rem;">
    <div style="max-width: 550px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
        <h2 style="color: #4f46e5; margin-top: 0;">🗓️ Reservation Coming Up!</h2>
        <p>Dear Professor,</p>
        <p>Your reservation starts in <strong>1 hour</strong>:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
            <tr><td style="padding: 0.5rem 0; font-weight: bold;">Room:</td><td>{{ $room }}</td></tr>
            <tr><td style="padding: 0.5rem 0; font-weight: bold;">Date:</td><td>{{ $date }}</td></tr>
            <tr><td style="padding: 0.5rem 0; font-weight: bold;">Time Slot:</td><td>{{ $time }}</td></tr>
            <tr><td style="padding: 0.5rem 0; font-weight: bold;">Activity:</td><td>{{ $act }}</td></tr>
        </table>
        <p style="margin-bottom: 1.5rem;">You can log in to view your full schedule.</p>
        <a href="http://127.0.0.1:8000" target="_blank" style="display: inline-block; background: #4f46e5; color: #fff; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">Login to PTC Reservation</a>
        <p style="margin-top: 2rem; font-size: 0.85rem; color: #777;">PTC MIS Team</p>
    </div>
</body>
</html>