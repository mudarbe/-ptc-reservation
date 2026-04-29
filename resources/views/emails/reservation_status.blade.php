<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reservation Status</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f7fc; padding: 2rem;">
    <div style="max-width: 550px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
        <h2 style="color: #4f46e5; margin-top: 0;">
            {!! $data['status'] == 'approved' ? '✅ Reservation Approved!' : '❌ Reservation Declined' !!}
        </h2>
        <p>Dear {{ $data['professor_name'] }},</p>

                @if($data['status'] == 'approved')
            <p>Your reservation request has been <strong>approved</strong>!</p>
            <table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Room:</td><td>{{ $data['room'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Date:</td><td>{{ $data['date'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Time Slot:</td><td>{{ $data['time_slot'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Activity:</td><td>{{ $data['activity'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Pax:</td><td>{{ $data['pax'] }}</td></tr>
            </table>
            <p>Please arrive on time. Thank you for using the PTC Reservation System.</p>
        @elseif($data['status'] == 'declined')
            <p>We regret to inform you that your reservation request has been <strong>declined</strong>.</p>
            @if(!empty($data['reason']))
                <p><strong>Reason:</strong> {{ $data['reason'] }}</p>
            @endif
            <table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Room:</td><td>{{ $data['room'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Date:</td><td>{{ $data['date'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Time Slot:</td><td>{{ $data['time_slot'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Activity:</td><td>{{ $data['activity'] }}</td></tr>
            </table>
            <p>You may submit a new reservation request at a later time.</p>
        @elseif($data['status'] == 'cancelled')
            <p>Your reservation has been <strong>cancelled</strong> by the administrator.</p>
            @if(!empty($data['reason']))
                <p><strong>Reason:</strong> {{ $data['reason'] }}</p>
            @endif
            <table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Room:</td><td>{{ $data['room'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Date:</td><td>{{ $data['date'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Time Slot:</td><td>{{ $data['time_slot'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Activity:</td><td>{{ $data['activity'] }}</td></tr>
                <tr><td style="padding: 0.5rem 0; font-weight: bold;">Pax:</td><td>{{ $data['pax'] }}</td></tr>
            </table>
            <p>You may submit a new reservation request at a later time.</p>
        @endif

        <p style="margin-bottom: 1.5rem;">Login here:</p>
        <a href="http://127.0.0.1:8000" target="_blank" style="display: inline-block; background: #4f46e5; color: #fff; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">PTC Reservation System</a>
        <p style="margin-top: 2rem; font-size: 0.85rem; color: #777;">PTC MIS Team</p>
    </div>
</body>
</html>