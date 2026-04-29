<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check Request Status - PTC Reservation</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/status.css') }}">
</head>
<body>
    <div class="card">
        <h2>Check Request Status</h2>
        <p class="subtitle">Enter the personal email you used when requesting an account.</p>

        <form action="{{ route('request.status.check') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="personal_email">Personal Email</label>
                <input type="email" id="personal_email" name="personal_email"
                       value="{{ old('personal_email') }}" placeholder="yourname@gmail.com" required>
            </div>
            <button type="submit" class="btn">Check Status</button>
        </form>

        @if(session('status_result'))
            @php $result = session('status_result'); @endphp
            <div class="result-box {{ $result['status'] }}">
                <h3 style="font-weight:600; margin-bottom:0.75rem;">Request Details</h3>
                <p><strong>Full Name:</strong> {{ $result['full_name'] }}</p>
                <p><strong>Institutional Email:</strong> {{ $result['institutional_email'] }}</p>
                <p><strong>Status:</strong> <span style="font-weight:600;">{{ ucfirst($result['status']) }}</span></p>
                @if($result['status'] == 'approved')
                    <p style="margin-top:0.75rem;">✅ Your account has been approved! You may now log in.</p>
                @elseif($result['status'] == 'rejected')
                    <p style="margin-top:0.75rem;">❌ Your request was declined. Contact MIS for info.</p>
                @else
                    <p style="margin-top:0.75rem;">⏳ Your request is under review. Check back later.</p>
                @endif
            </div>
        @elseif(session('not_found'))
            <div class="result-box not-found">
                <p>{{ session('not_found') }}</p>
            </div>
        @endif

        <a href="{{ url('/') }}" class="back-link">← Back to Login</a>
    </div>
</body>
</html>