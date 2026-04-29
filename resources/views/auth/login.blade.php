<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PTC Reservation - Login</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>

    <div class="login-wrapper">

        <!-- LEFT PANEL -->
        <div class="login-left">
            <div class="slide-text" id="slideText">
                <div class="slide-item active">
                    <div class="brand-title">PTC Reservation</div>
                    <div class="brand-sub">School Reservation System<br>Pateros Technological College</div>
                </div>
                <div class="slide-item">
                    <div class="brand-title">Smart Booking</div>
                    <div class="brand-sub">Book rooms with just a few clicks<br>Fast and intuitive</div>
                </div>
                <div class="slide-item">
                    <div class="brand-title">Always Up‑to‑Date</div>
                    <div class="brand-sub">Real‑time availability<br>No double bookings</div>
                </div>
                <div class="slide-item">
                    <div class="brand-title">Stay Informed</div>
                    <div class="brand-sub">Get notified on approvals<br>Never miss a change</div>
                </div>
            </div>

            <!-- Interactive feature list -->
            <div class="feature-section">
                <h3>What you can do:</h3>
                <div class="feature-card" id="featureCard" onclick="nextFeature()">
                    <span class="feature-text" id="featureText">📅 Book laboratory & lecture rooms</span>
                    <span class="feature-arrow">→</span>
                </div>
                <p class="feature-hint">Click the card to see next</p>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="login-right">
            <div class="logo-slot"></div>
            <h2>Welcome Back</h2>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" style="width:100%;">
                @csrf
                <div class="input-group">
                    <label for="institutional_email">Institutional Email</label>
                    <input type="email" id="institutional_email" name="institutional_email"
                           value="{{ old('institutional_email') }}"
                           placeholder="@paterostechnologicalcollege.edu.ph" required>
                    <span class="help-text">Your email is your password</span>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="divider"><span>or</span></div>

            <div class="btn-group">
                <button onclick="openModal('requestModal')" class="btn btn-outline">Request Account</button>
                <button onclick="openModal('statusModal')" class="btn btn-dark">View Request Status</button>
            </div>
        </div>
    </div>

    <!-- REQUEST ACCOUNT MODAL -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Account</h3>
                <button class="close-btn" onclick="closeModal('requestModal')">&times;</button>
            </div>
            <p style="color:#64748b; margin-bottom:1.5rem;">All requests are subject to admin approval.</p>
            <form action="{{ route('request.submit') }}" method="POST">
                @csrf
                <div class="input-group">
                    <label for="modal_full_name">Full Name</label>
                    <input type="text" id="modal_full_name" name="full_name"
                           value="{{ old('full_name') }}" placeholder="Enter your full name" required>
                </div>
                <div class="input-group">
                    <label for="modal_personal_email">Personal Email</label>
                    <input type="email" id="modal_personal_email" name="personal_email"
                           value="{{ old('personal_email') }}" placeholder="yourname@gmail.com" required>
                </div>
                <div class="input-group">
                    <label for="modal_institutional_email">Institutional Email</label>
                    <input type="email" id="modal_institutional_email" name="institutional_email"
                           value="{{ old('institutional_email') }}"
                           placeholder="yourname@paterostechnologicalcollege.edu.ph"
                           pattern="[a-zA-Z0-9._%+-]+@paterostechnologicalcollege\.edu\.ph"
                           title="Must be a valid @paterostechnologicalcollege.edu.ph email" required>
                    <span class="help-text">This will be your login email and password.</span>
                </div>

                @if($errors->any() && old('_token'))
                    <div class="alert alert-error" style="margin-top:1rem;">
                        <ul style="padding-left:1.2rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Submit Request</button>
            </form>
        </div>
    </div>

    <!-- VIEW STATUS MODAL -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Check Request Status</h3>
                <button class="close-btn" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <p style="color:#64748b; margin-bottom:1.5rem;">Enter the personal email you used when requesting an account.</p>
            <form action="{{ route('request.status.check') }}" method="POST">
                @csrf
                <div class="input-group">
                    <label for="status_personal_email">Personal Email</label>
                    <input type="email" id="status_personal_email" name="personal_email"
                           value="{{ old('personal_email') }}" placeholder="yourname@gmail.com" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Check Status</button>
            </form>

            @if(session('status_result'))
                @php $result = session('status_result'); @endphp
                <div class="status-result {{ $result['status'] }}">
                    <h4 style="font-weight:600; margin-bottom:0.5rem;">Request Details</h4>
                    <p><strong>Full Name:</strong> {{ $result['full_name'] }}</p>
                    <p><strong>Institutional Email:</strong> {{ $result['institutional_email'] }}</p>
                    <p><strong>Status:</strong> <span style="font-weight:600;">{{ ucfirst($result['status']) }}</span></p>
                    @if($result['status'] == 'approved')
                        <p style="margin-top:0.5rem;">✅ Your account has been approved! You may now log in.</p>
                    @elseif($result['status'] == 'rejected')
                        <p style="margin-top:0.5rem;">❌ Your request was declined. Contact MIS for info.</p>
                    @else
                        <p style="margin-top:0.5rem;">⏳ Your request is under review. Check back later.</p>
                    @endif
                </div>
            @elseif(session('not_found'))
                <div class="alert alert-error" style="margin-top:1rem;">
                    {{ session('not_found') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        // Slideshow for left panel titles
        (function() {
            const slides = document.querySelectorAll('#slideText .slide-item');
            let currentSlide = 0;
            function showSlide(index) {
                slides.forEach(s => s.classList.remove('active'));
                slides[index].classList.add('active');
            }
            showSlide(0);
            setInterval(() => {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }, 4000);
        })();

        // ---------- CLICKABLE FEATURES ----------
        const professorFeatures = [
            '📅 Book laboratory & lecture rooms',
            '⏰ Choose from available time slots',
            '📋 Track your reservation history',
            '🔔 Receive email notifications'
        ];
        let currentFeature = 0;
        const featureText = document.getElementById('featureText');

        function nextFeature() {
            currentFeature = (currentFeature + 1) % professorFeatures.length;
            featureText.textContent = professorFeatures[currentFeature];
        }

        // Make sure the first item is shown
        featureText.textContent = professorFeatures[0];

        // ---------- MODAL FUNCTIONS ----------
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if($errors->any() && old('_token'))
                openModal('requestModal');
            @endif
            @if(session('status_result') || session('not_found'))
                openModal('statusModal');
            @endif
        });
    </script>
</body>
</html>