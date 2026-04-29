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

    // System users popup
    usersPopup: false,
    allUsers: [],
    usersLoading: false,

    // Upcoming reservation modal
    upcomingModal: false,
    upcomingDetail: null,

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

    openPopup(title, dataPromise) {
        this.popupTitle = title;
        this.popupLoading = true;
        this.popupOpen = true;
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
        <div class="sidebar-header">
            <h2>MIS Admin</h2>
            <p>PTC Reservation</p>
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
                <!-- Total Users (opens popup directly) -->
                <div class="stat-card stat-card-purple clickable-stat" @click="openUsersPopup()">
                    <h3>Total Users</h3>
                    <p class="stat-number">{{ \App\Models\SystemUser::count() }}</p>
                </div>

                <!-- Pending Account Requests (popup) -->
                <div class="stat-card stat-card-yellow clickable-stat"
                     @click="openPopup('Pending Account Requests',
                         fetch('/api/admin/pending-account-requests')
                             .then(r => r.json())
                     )">
                    <h3>Pending Account Requests</h3>
                    <p class="stat-number">{{ \App\Models\AccountRequest::where('status', 'pending')->count() }}</p>
                </div>

                <!-- Pending Reservations (popup) -->
                <div class="stat-card stat-card-orange clickable-stat"
                     @click="openPopup('Pending Reservation Requests',
                         fetch('/api/admin/pending-reservations')
                             .then(r => r.json())
                     )">
                    <h3>Pending Reservations</h3>
                    <p class="stat-number">{{ \App\Models\Reservation::where('status', 'pending')->where('hold_expires_at', '>', now())->count() }}</p>
                </div>

                <!-- Active Reservations (popup) -->
                <div class="stat-card stat-card-green clickable-stat"
                     @click="openPopup('Active Reservations',
                         fetch('/api/admin/active-reservations')
                             .then(r => r.json())
                     )">
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
                <div class="table-wrapper" style="margin-top:2rem;">
                    <h3>📋 Activity Logs</h3>
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
                            @foreach($activityLogs as $log)
                            <tr>
                                <td>{{ $log->user ? $log->user->full_name : 'System' }}</td>
                                <td>
                                    <span class="badge 
                                        @if(in_array($log->action, ['approved'])) badge-approved
                                        @elseif(in_array($log->action, ['declined','cancelled'])) badge-declined
                                        @elseif($log->action == 'checked_in') badge-ongoing
                                        @elseif($log->action == 'blocked') badge-expired
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
                        {{ $up->status === 'ongoing' ? '🟢 Ongoing' : '📅 Upcoming' }} Reservation
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
                            <div>
                                <p><strong>Professor:</strong> <span x-text="upcomingDetail.professor"></span></p>
                                <p><strong>Room:</strong> <span x-text="upcomingDetail.room"></span> (<span x-text="upcomingDetail.room_type"></span>)</p>
                                <p><strong>Date:</strong> <span x-text="upcomingDetail.date"></span></p>
                                <p><strong>Time:</strong> <span x-text="upcomingDetail.time_slot"></span></p>
                                <p><strong>Activity:</strong> <span x-text="upcomingDetail.activity"></span></p>
                                <p><strong>Pax:</strong> <span x-text="upcomingDetail.pax"></span></p>
                                <p><strong>Status:</strong> <span x-text="upcomingDetail.status"></span></p>
                                <div style="margin-top: 1.5rem; text-align: right;">
                                    <a href="{{ route('admin.reservations.active') }}" class="btn btn-primary">View Active Reservations</a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @else
                <div class="upcoming-card" style="margin-top:2rem; background: #f8fafc; cursor: default;">
                    <p style="color: var(--color-muted); text-align: center;">📅 No upcoming reservations</p>
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
                                    <tr>
                                        <td>
                                            <span x-text="item.full_name || item.user?.full_name || '—'"></span>
                                            <div style="font-size:0.8rem; color:var(--color-muted);" x-text="item.personal_email || item.user?.personal_email || ''"></div>
                                        </td>
                                        <td x-text="item.room?.name || '—'"></td>
                                        <td x-text="item.reservation_date || item.created_at?.substr(0,10) || '—'"></td>
                                        <td x-text="item.time_slot || '—'"></td>
                                        <td x-text="item.activity_name || '—'"></td>
                                        <td x-text="item.pax || '—'"></td>
                                        <td>
                                            <span class="badge" :class="'badge-' + (item.status || 'pending')" x-text="item.status?.charAt(0).toUpperCase() + item.status?.slice(1)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
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

    <!-- Footer -->
    <footer style="background: #111827; color: #94a3b8; text-align: center; padding: 1rem; font-size: 0.85rem;">
        Pateros Technological College &copy; {{ date('Y') }}
    </footer>

</body>
</html>