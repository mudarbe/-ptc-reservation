<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MIS Admin - PTC Reservation</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    popupOpen: false,
    popupTitle: '',
    popupData: [],
    popupLoading: false,
    selectedReservationId: null,
selectedAccountRequestId: null,

    // System users popup
    usersPopup: false,
    allUsers: [],
    usersLoading: false,

    // Upcoming reservation modal
    upcomingModal: false,
    upcomingDetail: null,
    dailyMonitorOpen: false,
monitorDate: '{{ now()->toDateString() }}',

    get viewAllLink() {
        switch(this.popupTitle) {
            case 'Pending Account Requests':
                return '{{ route("admin.account_requests") }}';
            case 'Pending Reservation Requests':
                return '{{ route("admin.reservations.pending") }}';
            case 'Active Reservations':
                return '{{ route("admin.reservations.active") }}';
            default:
                return '#';
        }
    },

    openPopup(title, dataPromise, selectedId = null) {

    this.popupTitle = title;

    this.popupLoading = true;

    this.popupOpen = true;

    this.selectedReservationId = selectedId;

    this.selectedAccountRequestId = selectedId;

    dataPromise.then(data => {

        this.popupData = data;

        this.popupLoading = false;

    });

},

    openUsersPopup() {
        this.usersPopup = true;
        this.usersLoading = true;
        fetch('/api/admin/system-users')
            .then(r => r.json())
            .then(data => {
                this.allUsers = data;
                this.usersLoading = false;
            });
    }
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
            <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
            <a href="{{ route('admin.account_requests') }}">Account Requests</a>
            <a href="{{ route('admin.reservations.pending') }}">Reservation Requests</a>
            <a href="{{ route('admin.reservations.active') }}">Active Reservations</a>
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
            <h1>Dashboard</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            <!-- Stats Cards -->
            <div class="stats-grid">

    <!-- Total Users -->
    <div class="stat-card stat-card-purple clickable-stat" @click="openUsersPopup()">
        <div class="stat-icon users-icon">

<svg width="38"
height="38"
     viewBox="0 0 24 24"
     fill="none"
     xmlns="http://www.w3.org/2000/svg">

    <circle cx="9"
            cy="8"
            r="4"
            stroke="currentColor"
            stroke-width="2"/>

    <path d="M3 20C3 16.6863 5.68629 14 9 14C12.3137 14 15 16.6863 15 20"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"/>

    <circle cx="18"
            cy="18"
            r="3"
            stroke="currentColor"
            stroke-width="2"/>

    <path d="M16.8 18L17.7 18.9L19.5 17.1"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"/>

</svg>

</div>
        <h3>Total Users</h3>
        <p class="stat-number">{{ \App\Models\SystemUser::count() }}</p>
    </div>

    <!-- Pending Account Requests -->
    <div class="stat-card stat-card-yellow clickable-stat"
         @click="
openPopup(
    'Pending Account Requests',
    fetch('/api/admin/pending-account-requests')
        .then(r => r.json())
)
">
        <div class="stat-icon pending-account-icon">

<svg width="38"
height="38"
     viewBox="0 0 24 24"
     fill="none"
     xmlns="http://www.w3.org/2000/svg">

    <circle cx="12"
            cy="12"
            r="9"
            stroke="currentColor"
            stroke-width="2.2"/>

    <path d="M12 7V12L15 14"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"/>

</svg>

</div>
        <h3>Pending Account Requests</h3>
        <p class="stat-number">{{ \App\Models\AccountRequest::where('status', 'pending')->count() }}</p>
    </div>

    <!-- Pending Reservations -->
    <div class="stat-card stat-card-orange clickable-stat"
         @click="
openPopup(
    'Pending Reservation Requests',
    fetch('/api/admin/pending-reservations')
        .then(r => r.json())
)
">
        <div class="stat-icon pending-reservation-icon">

<svg width="38"
height="38"
     viewBox="0 0 24 24"
     fill="none"
     xmlns="http://www.w3.org/2000/svg">

    <circle cx="12"
            cy="12"
            r="9"
            stroke="currentColor"
            stroke-width="2.2"/>

    <path d="M12 7V12L15 14"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"/>

</svg>

</div>
        <h3>Pending Reservations</h3>
        <p class="stat-number">{{ \App\Models\Reservation::where('status', 'pending')->where('hold_expires_at', '>', now())->count() }}</p>
    </div>

    <!-- Active Reservations -->
    <div class="stat-card stat-card-green clickable-stat"
         @click="
openPopup(
    'Active Reservations',
    fetch('/api/admin/active-reservations')
        .then(r => r.json())
)
">
        <div class="stat-icon active-icon">

<svg width="38"
height="38"
     viewBox="0 0 24 24"
     fill="none"
     xmlns="http://www.w3.org/2000/svg">

    <circle cx="12"
            cy="12"
            r="9"
            stroke="currentColor"
            stroke-width="2.2"/>

    <path d="M8 12.5L10.8 15L16 9"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"/>

</svg>

</div>
        <h3>Active Reservations</h3>
        <p class="stat-number">{{ \App\Models\Reservation::whereIn('status', ['approved','ongoing'])->count() }}</p>
    </div>

</div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="{{ route('admin.account_requests') }}" class="btn btn-primary">View Account Requests</a>
                <a href="{{ route('admin.reservations.pending') }}" class="btn btn-warning">View Pending Reservations</a>
            </div>

                        <!-- Activity Logs -->
            @php
                $activityLogs = \App\Models\ActivityLog::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            @endphp

            @if($activityLogs->count() > 0)
                <div class="table-wrapper" style="margin-top:2rem;" x-data="{ expanded: false }">
                    <h3>Activity Logs</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Date / Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLogs as $index => $log)
                            <tr style="{{ $index >= 3 ? 'display: none;' : '' }}"
                                :style="{{ $index >= 3 ? 'expanded ? \'display: table-row;\' : \'display: none;\'' : '' }}">
                                <td>{{ $log->user ? $log->user->full_name : 'System' }}</td>
                                <td>
                                    <span class="badge 
                                        @if(in_array($log->action, ['approved'])) badge-approved
                                        @elseif(in_array($log->action, ['declined','cancelled'])) badge-declined
                                        @elseif($log->action == 'checked_in') badge-ongoing
                                        @elseif($log->action == 'blocked') badge-expired
                                        @elseif($log->action == 'reminder_sent') badge-pending
                                        @else badge-pending
                                        @endif">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($activityLogs->count() > 3)
                        <div style="text-align: center; margin-top: 0.5rem;">
                            <button class="btn btn-outline-filter" @click="expanded = !expanded">
                                <span x-text="expanded ? '▲ Less' : '▼ More'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Upcoming Reservations -->
            @php
                $upcomingReservations = \App\Models\Reservation::whereIn('status', ['approved', 'ongoing'])
                    ->where('reservation_date', '>=', now()->toDateString())
                    ->with('user', 'room')
                    ->orderBy('reservation_date')
                    ->orderBy('time_slot')
                    ->limit(1)
                    ->get();
            @endphp

            @if($upcomingReservations->count() > 0)
                @php $up = $upcomingReservations->first(); @endphp
                <div class="upcoming-card" style="margin-top:2rem;" @click="upcomingDetail = {{ json_encode([
                    'id' => $up->id,
                    'professor' => $up->user->full_name,
                    'room' => $up->room->name,
                    'room_type' => ucfirst($up->room->type),
                    'date' => $up->reservation_date->format('M d, Y'),
                    'time_slot' => $up->time_slot,
                    'activity' => $up->activity_name,
                    'pax' => $up->pax,
                    'status' => ucfirst($up->status),
                ]) }}; upcomingModal = true">
                    <h4 class="upcoming-title" style="color: var(--color-text);">
                        {{ $up->status === 'ongoing' ? '🟢 Ongoing' : ' Upcoming' }} Reservation
                    </h4>
                    <div class="upcoming-minimal">
                        <span class="upcoming-date-small">{{ $up->reservation_date->format('M d, Y') }}</span>
                        <span class="upcoming-slot-small">{{ $up->time_slot }}</span>
                        <span class="upcoming-arrow">→</span>
                    </div>
                    <div class="upcoming-click-hint">Tap for details</div>
                </div>

                <!-- Upcoming Detail Modal -->
                <div x-show="upcomingModal" class="modal-overlay" @click.self="upcomingModal = false">
                    <div class="modal-box" style="max-width: 500px;">
                        <div class="modal-header">
                            <h3>Reservation Details</h3>
                            <button @click="upcomingModal = false" class="modal-close">&times;</button>
                        </div>
                        <template x-if="upcomingDetail">

    <div class="modern-detail-container">

        <div class="modern-detail-top">

            <div class="modern-detail-icon">
                📅
            </div>

            <div>
                <h2 class="modern-detail-title">
                    Reservation Details
                </h2>

                <p class="modern-detail-sub">
                    Upcoming / Ongoing Reservation
                </p>
            </div>

        </div>

        <div class="modern-detail-grid">

            <div class="modern-detail-card">
                <span>Professor</span>
                <strong x-text="upcomingDetail.professor"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Room</span>
                <strong>
                    <span x-text="upcomingDetail.room"></span>
                </strong>
            </div>

            <div class="modern-detail-card">
                <span>Room Type</span>
                <strong x-text="upcomingDetail.room_type"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Date</span>
                <strong x-text="upcomingDetail.date"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Time Slot</span>
                <strong x-text="upcomingDetail.time_slot"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Activity</span>
                <strong x-text="upcomingDetail.activity"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Pax</span>
                <strong x-text="upcomingDetail.pax"></strong>
            </div>

            <div class="modern-detail-card">
                <span>Status</span>
                <strong x-text="upcomingDetail.status"></strong>
            </div>

        </div>

        <div class="modern-detail-actions">

            <button
                @click="upcomingModal = false"
                class="btn btn-outline">

                Close

            </button>

            <a href="{{ route('admin.reservations.active') }}"
               class="btn btn-primary">

                View Reservations

            </a>

        </div>

    </div>

</template>
                    </div>
                </div>
            @else
                <div class="upcoming-card" style="margin-top:2rem; background: #f8fafc; cursor: default;">
                    <p style="color: var(--color-muted); text-align: center;"> No upcoming reservations</p>
                </div>
            @endif
        </div>
    </div>

    <!-- POPUP MODAL for quick stats (Pending Account Requests, Pending Reservations, Active Reservations) -->
    <div x-show="popupOpen" class="modal-overlay" @click.self="popupOpen = false">
        <div class="modal-box" style="max-width: 800px;">
            <div class="modal-header">
                <h3 x-text="popupTitle"></h3>
                <button @click="popupOpen = false" class="modal-close">&times;</button>
            </div>
            <div x-show="popupLoading" style="text-align:center; padding:2rem;">Loading...</div>
            <template x-if="!popupLoading && popupData.length === 0">
                <div class="empty-state">No records found.</div>
            </template>
            <template x-if="!popupLoading && popupData.length > 0">
                <div>
                    <div class="table-wrapper" style="max-height: 60vh; overflow-y: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name / Email</th>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Activity</th>
                                    <th>Pax</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in popupData" :key="item.id">

<tr style="cursor:pointer;"

    @click="

        if(popupTitle === 'Pending Account Requests') {

            window.location.href =
            '{{ route('admin.account_requests') }}?highlight=' + item.id;

        }

        else if(popupTitle === 'Pending Reservation Requests') {

            window.location.href =
            '{{ route('admin.reservations.pending') }}?highlight=' + item.id;

        }

        else if(popupTitle === 'Active Reservations') {

            window.location.href =
            '{{ route('admin.reservations.active') }}?highlight=' + item.id;

        }

    "
>

    <td>
        <span x-text="item.full_name || item.user?.full_name || '—'"></span>

        <div style="font-size:0.8rem; color:var(--color-muted);"
             x-text="item.institutional_email || item.user?.institutional_email || ''">
        </div>
    </td>

    <td x-text="item.room?.name || '—'"></td>

    <td x-text="item.reservation_date || item.created_at?.substr(0,10) || '—'"></td>

    <td x-text="item.time_slot || '—'"></td>

    <td x-text="item.activity_name || '—'"></td>

    <td x-text="item.pax || '—'"></td>

    <td>

        <span class="badge"
              :class="'badge-' + (item.status || 'pending')"
              x-text="item.status?.charAt(0).toUpperCase() + item.status?.slice(1)">
        </span>

    </td>

</tr>

</template>
                    <!-- View All button -->
                    <div style="text-align: right; margin-top: 1rem;">
                        <a :href="viewAllLink" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- POPUP MODAL for All System Users -->
    <div x-show="usersPopup" class="modal-overlay" @click.self="usersPopup = false">
        <div class="modal-box" style="max-width: 900px;">
            <div class="modal-header">
                <h3>All System Users</h3>
                <button @click="usersPopup = false" class="modal-close">&times;</button>
            </div>
            <div x-show="usersLoading" style="text-align:center; padding:2rem;">Loading users...</div>
            <template x-if="!usersLoading && allUsers.length === 0">
                <div class="empty-state">No users found.</div>
            </template>
            <template x-if="!usersLoading && allUsers.length > 0">
                <div class="table-wrapper" style="max-height: 65vh; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Institutional Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="user in allUsers" :key="user.id">
                                <tr>
                                    <td x-text="user.full_name"></td>
                                    <td x-text="user.institutional_email"></td>
                                    <td>
                                        <span class="badge-role" :class="user.role === 'mis' ? 'mis' : 'professor'" x-text="user.role.charAt(0).toUpperCase() + user.role.slice(1)"></span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="user.is_active ? 'badge-approved' : 'badge-declined'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

                <!-- Floating Daily Monitor Button -->



                <!-- Daily Monitoring Modal -->

<div x-show="dailyMonitorOpen"
     class="modal-overlay"
     @click.self="dailyMonitorOpen = false">

<div class="modal-box"
     style="max-width:1400px; width:95%;">

    <div class="modal-header">

        <h2>
            📋 Daily Room Monitoring
        </h2>

        <button class="modal-close"
                @click="dailyMonitorOpen = false">

            &times;

        </button>

    </div>

    <!-- DATE NAVIGATION -->

    <div class="monitor-nav">

        <button class="monitor-nav-btn"

                @click="
                    let d = new Date(monitorDate);
                    d.setDate(d.getDate() - 1);
                    monitorDate = d.toISOString().split('T')[0];
                ">

            ◀ Previous

        </button>

        <div class="monitor-current-date"
             x-text="monitorDate">
        </div>

        <button class="monitor-nav-btn"

                @click="
                    let d = new Date(monitorDate);
                    d.setDate(d.getDate() + 1);
                    monitorDate = d.toISOString().split('T')[0];
                ">

            Next ▶

        </button>

    </div>

    @php

        $rooms =
            \App\Models\Room::where('is_active', true)
            ->get();

        

        $allReservations =
    \App\Models\Reservation::whereIn(
        'status',
        ['approved','ongoing','done']
    )
            ->with('user','room')
            ->get();

    @endphp

    <div class="daily-monitor-grid">

        @foreach($rooms as $room)

@php

$timeSlots = [

    '7:00 AM - 10:00 AM',
    '10:00 AM - 1:00 PM',
    '1:00 PM - 4:00 PM',
    '4:00 PM - 7:00 PM',

    '7:00 AM - 12:00 PM',
    '12:00 PM - 4:00 PM',
    '4:00 PM - 9:00 PM'

];


@endphp

<div class="monitor-room-card">

            <div class="monitor-room-header">

                <h3>
    {{ $room->name }}
</h3>

<div class="monitor-room-type">

    {{ ucfirst($room->type) }} Room

</div>

            </div>

            <div class="monitor-slot-list">

                @foreach($timeSlots as $slot)

@php

$matching =
    $allReservations
    ->where('room_id', $room->id)
    ->where('time_slot', $slot);

@endphp

<div class="monitor-slot-card">

                     x-show="
                        monitorDate >= '{{ now()->toDateString() }}'
                     ">

                    <div class="monitor-slot-time">

                        {{ $slot }}

                    </div>

                    <template x-if="true">

                        <div>

                            

@if($matching->count() > 0)

@foreach($matching as $reservation)

<div class="monitor-reservation-box"

     x-show="
        monitorDate ===
        '{{ $reservation->reservation_date->toDateString() }}'
     ">

    <div class="monitor-prof">

        {{ $reservation->user->full_name }}

    </div>

    <div class="monitor-activity">

        {{ $reservation->activity_name }}

    </div>

    <div class="monitor-pax">

        👥 {{ $reservation->pax }} pax

    </div>

    <div class="monitor-status">

        {{ ucfirst($reservation->status) }}

    </div>

    @if(
        $reservation->checked_in_at
        ||
        $reservation->status === 'ongoing'
    )

    @php

        $monitorEnd =
            \Carbon\Carbon::parse(
                $reservation->reservation_date
                ->toDateString().' '.
                parseSlotEndTime(
                    $reservation->time_slot
                ),
                'Asia/Manila'
            );

    @endphp

    <div class="monitor-timer"

         x-data="{ remaining: '' }"

         x-init="

            const endTime =
            new Date(
                '{{ $monitorEnd->toDateTimeString() }}'
            ).getTime();

            const update = () => {

                const now =
                new Date().getTime();

                const diff =
                endTime - now;

                if(diff <= 0){

                    remaining = 'Done';

                    return;
                }

                const h =
                Math.floor(diff / 3600000);

                const m =
                Math.floor((diff % 3600000) / 60000);

                const s =
                Math.floor((diff % 60000) / 1000);

                remaining =
                    h + ':' +
                    m.toString().padStart(2,'0') +
                    ':' +
                    s.toString().padStart(2,'0');

            };

            update();

            setInterval(update,1000);

         ">

        ⏳
        <span x-text="remaining"></span>

    </div>

    @endif

</div>

@endforeach

@else

<div class="monitor-empty">

    Available

</div>

@endif

</div>



                        </div>

                    </template>

                </div>

                @endforeach

            </div>

        </div>

        @endforeach

    </div>

</div>

</div>

    <!-- Footer -->
    <footer style="background: #111827; color: #94a3b8; text-align: center; padding: 1rem; font-size: 0.85rem;">
        Pateros Technological College &copy; {{ date('Y') }}
    </footer>

</body>
</html>