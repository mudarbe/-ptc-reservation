<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Account - PTC Reservation</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/request.css') }}">
</head>
<body>
    <div class="card">
        <h2>Request Account</h2>
        <p class="subtitle">All requests are subject to admin approval</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul style="padding-left:1.2rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('request.submit') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="{{ old('full_name') }}" placeholder="Enter your full name" required>
            </div>
            
            <div class="input-group">
                <label for="institutional_email">Institutional Email</label>
                <input type="email" id="institutional_email" name="institutional_email"
                       value="{{ old('institutional_email') }}"
                       placeholder="yourname@paterostechnologicalcollege.edu.ph"
                       pattern="[a-zA-Z0-9._%+-]+@paterostechnologicalcollege\.edu\.ph"
                       title="Must be a valid @paterostechnologicalcollege.edu.ph email" required>
                <span class="help-text">This will be your login email and password.</span>
            </div>
            <button type="submit" class="btn">Submit Request</button>
        </form>

        <a href="{{ url('/') }}" class="back-link">← Back to Login</a>
    </div>
</body>
</html>