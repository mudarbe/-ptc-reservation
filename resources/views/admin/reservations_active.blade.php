<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Active Reservations - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    selectedDate: '',
    cancelModal: false,
    selectedRes: null,
    reasonType: 'schedule_conflict',
    reasonNotes: '',

    // Detail modal
    detailModal: false,
    detailRes: null
}">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header" style="display:flex; align-items:center; gap:0.75rem;">
    <img src="{{ asset('images/ptc_logo.png') }}" alt="PTC Logo" style="height: 36px; width: auto; border-radius: 8px;">
    <div>
        <h2>MIS Admin</h2>
        <p>PTC Reservation</p>
    </div>
</div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.account_requests') }}">Account Requests</a>
            <a href="{{ route('admin.reservations.pending') }}">Reservation Requests</a>
            <a href="{{ route('admin.reservations.active') }}" class="active">Active Reservations</a>
            <a href="{{ route('admin.reservations.archived') }}">Archived</a>
            <a href="{{ route('admin.users') }}">Manage Users</a>
            <a href="{{ route('admin.rooms.index') }}">Rooms</a>
        </nav>
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-header">
            <h1>Active Reservations</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            <div class="archive-filter-bar">

    <div class="archive-filter-left">

        <label>Filter by Date</label>

        <input type="date"
               x-model="selectedDate"
               class="archive-date-filter">

    </div>

    <button class="archive-clear-btn"
            x-show="selectedDate"
            @click="selectedDate = ''">

        Clear

    </button>

</div>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <!-- Cancellation Email Banner -->
            @if(session('show_cancellation_email') && session('cancelled_reservation'))
                @php $data = session('cancelled_reservation'); @endphp
                @php
                    $subject = "PTC Reservation - Booking Cancelled";
                    $body = "Dear {$data['professor_name']},\n\n";
                    $body .= "Your reservation has been cancelled.\n";
                    $body .= "Reason: {$data['reason']}\n\n";
                    $body .= "Reservation Details:\n";
                    $body .= "- Room: {$data['room']}\n";
                    $body .= "- Date: {$data['date']}\n";
                    $body .= "- Time Slot: {$data['time_slot']}\n";
                    $body .= "- Activity: {$data['activity']}\n";
                    $body .= "- Pax: {$data['pax']}\n\n";
                    $body .= "You may submit a new request using this link:\n";
                    $body .= "http://127.0.0.1:8000/\n\n";
                    $body .= "Best regards,\nPTC MIS Team";
                    $mailto = "mailto:{$data['professor_email']}?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
                @endphp
                <div class="email-banner">
                    <div class="banner-text">
                        <h4>Notify {{ $data['professor_name'] }}</h4>
                        <p>{{ $data['professor_email'] }}</p>
                    </div>
                    <a href="{{ $mailto }}" target="_blank" class="email-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Send Email
                    </a>
                </div>
            @endif

            @if(isset($reservations) && $reservations->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        
                        <tbody>
                            @foreach($reservations as $res)

<tr
    x-show="
        !selectedDate ||
        selectedDate === '{{ $res->reservation_date->format('Y-m-d') }}'
    "

<tr id="reservation-{{ $res->id }}"
    class="clickable-row {{ request('highlight') == $res->id ? 'highlight-row' : '' }}"
                                @click="detailRes = {{ json_encode([
                                    'professor' => $res->user->full_name,
                                    'room' => $res->room->name,
                                    'date' => $res->reservation_date->format('M d, Y'),
                                    'time_slot' => $res->time_slot,
                                    'activity' => $res->activity_name,
                                    'pax' => $res->pax,
                                    'status' => ucfirst($res->status),
                                ]) }}; detailModal = true">
                                <td>{{ $res->user->full_name }}</td>
                                <td>{{ $res->room->name }}</td>
                                <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                                <td>{{ $res->time_slot }}</td>
                                <td>{{ $res->activity_name }}</td>
                                <td>{{ $res->pax }}</td>
                                <td>
                                    <span class="badge {{ $res->status == 'approved' ? 'badge-approved' : 'badge-ongoing' }}">
                                        {{ ucfirst($res->status) }}
                                    </span>
                                </td>
                                <td @click.stop="">
                                    <button 
                                        @click="cancelModal = true; selectedRes = {{ $res->id }}; reasonType = 'schedule_conflict'; reasonNotes = ''" 
                                        class="cancel-link">
                                        Cancel
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-wrapper">
                    <div class="empty-state">No active reservations.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Cancel Modal -->
    <div x-show="cancelModal" class="modal-overlay" style="display: none;" @click.self="cancelModal = false">
        <div class="modal-box">
            <h3>Cancel Reservation</h3>
            <form :action="'/admin/reservations/' + selectedRes + '/cancel'" method="POST">
                @csrf
                <label>Reason</label>
                <select name="reason_type" x-model="reasonType" required>
                    <option value="schedule_conflict">Schedule Conflict</option>
                    <option value="unavailable">Unavailable</option>
                    <option value="duplicate">Duplicate</option>
                    <option value="invalid">Invalid</option>
                    <option value="other">Other</option>
                </select>

                <div x-show="reasonType === 'other'">
                    <label>Specify Reason</label>
                    <textarea name="reason_notes" x-model="reasonNotes" rows="2"></textarea>
                </div>

                <div class="note">After cancellation, an email notification will appear.</div>

                <div class="modal-actions">
                    <button type="button" @click="cancelModal = false" class="btn-close">Close</button>
                    <button type="submit" class="btn-sm btn-decline">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="detailModal" class="modal-overlay" @click.self="detailModal = false">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Reservation Details</h3>
                <button @click="detailModal = false" class="modal-close">&times;</button>
            </div>
            <template x-if="detailRes">

    <div class="account-detail-modern">

        <!-- TOP -->

        <div class="account-detail-top">

            <div class="account-avatar">

                <span x-text="detailRes.professor.charAt(0).toUpperCase()"></span>

            </div>

            <div>

                <h2 x-text="detailRes.professor"></h2>

                <p>
                    Active Reservation
                </p>

            </div>

        </div>

        <!-- DETAILS -->

        <div class="account-detail-grid">

            <div class="account-detail-item">

                <span>Room</span>

                <strong x-text="detailRes.room"></strong>

            </div>

            <div class="account-detail-item">

                <span>Date</span>

                <strong x-text="detailRes.date"></strong>

            </div>

            <div class="account-detail-item">

                <span>Time Slot</span>

                <strong x-text="detailRes.time_slot"></strong>

            </div>

            <div class="account-detail-item">

                <span>Activity</span>

                <strong x-text="detailRes.activity"></strong>

            </div>

            <div class="account-detail-item">

                <span>Pax</span>

                <strong x-text="detailRes.pax"></strong>

            </div>

            <div class="account-detail-item">

                <span>Status</span>

                <div class="account-status-pill">

                    <span x-text="detailRes.status"></span>

                </div>

            </div>

        </div>

    </div>

</template>
            <div class="modal-actions" style="margin-top: 1.5rem;">
                <button @click="detailModal = false" class="btn btn-outline">Close</button>
            </div>
        </div>
    </div>

    <script>

window.addEventListener('load', () => {

    const highlighted =
        document.querySelector('.highlight-row');

    if(highlighted){

        highlighted.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    }

});

</script>

</body>
</html>