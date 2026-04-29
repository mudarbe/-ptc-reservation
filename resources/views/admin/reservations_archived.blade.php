<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archived Reservations - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    activeFilter: 'all',
    hiddenIds: [],
    reservations: {{ json_encode(
        $reservations->map(function($r) {
            return [
                'id' => $r->id,
                'professor' => $r->user->full_name,
                'room' => $r->room->name,
                'date' => $r->reservation_date->format('M d, Y'),
                'time_slot' => $r->time_slot,
                'activity' => $r->activity_name,
                'pax' => $r->pax,
                'status' => $r->status,
                'remarks' => $r->admin_remarks,
            ];
        })
    ) }},
    get filteredReservations() {
        if (this.activeFilter === 'hidden') {
            // show only hidden reservations
            return this.reservations.filter(r => this.hiddenIds.includes(r.id));
        }
        // for all other filters, exclude hidden items
        let visible = this.reservations.filter(r => !this.hiddenIds.includes(r.id));
        if (this.activeFilter === 'all') return visible;
        if (this.activeFilter === 'maintenance') {
            return visible.filter(r => {
                return r.status === 'cancelled' && r.remarks && (r.remarks.toLowerCase().includes('maintenance') || r.remarks.toLowerCase().includes('admin use'));
            });
        }
        return visible.filter(r => r.status === this.activeFilter);
    },
    hideReservation(id) {
        if (!this.hiddenIds.includes(id)) {
            this.hiddenIds.push(id);
        }
    },
    unhideReservation(id) {
        this.hiddenIds = this.hiddenIds.filter(i => i !== id);
    }
}">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>MIS Admin</h2>
            <p>PTC Reservation</p>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.account_requests') }}">Account Requests</a>
            <a href="{{ route('admin.reservations.pending') }}">Reservation Requests</a>
            <a href="{{ route('admin.reservations.active') }}">Active Reservations</a>
            <a href="{{ route('admin.reservations.archived') }}" class="active">Archived</a>
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
            <h1>Archived Reservations</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <!-- Filter Buttons -->
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.5rem;">
                <button @click="activeFilter = 'all'"
                        :class="activeFilter === 'all' ? 'btn btn-primary' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    All
                </button>
                <button @click="activeFilter = 'done'"
                        :class="activeFilter === 'done' ? 'btn btn-primary' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    Done
                </button>
                <button @click="activeFilter = 'cancelled'"
                        :class="activeFilter === 'cancelled' ? 'btn btn-danger' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    Cancelled
                </button>
                <button @click="activeFilter = 'declined'"
                        :class="activeFilter === 'declined' ? 'btn btn-danger' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    Declined
                </button>
                <button @click="activeFilter = 'expired'"
                        :class="activeFilter === 'expired' ? 'btn btn-warning' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    Expired
                </button>
                <button @click="activeFilter = 'maintenance'"
                        :class="activeFilter === 'maintenance' ? 'btn btn-warning' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    Maintenance/Admin Use
                </button>
                <!-- Hidden filter -->
                <button @click="activeFilter = 'hidden'"
                        :class="activeFilter === 'hidden' ? 'btn btn-primary' : 'btn btn-outline-filter'"
                        style="padding:0.4rem 1rem; font-size:0.85rem; border-radius:999px;">
                    👁️ Hidden
                </button>
            </div>

            <!-- Table -->
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Professor</th>
                            <th>Room</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Activity</th>
                            <th>Pax</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="res in filteredReservations" :key="res.id">
                            <tr>
                                <td x-text="res.professor"></td>
                                <td x-text="res.room"></td>
                                <td x-text="res.date"></td>
                                <td x-text="res.time_slot"></td>
                                <td x-text="res.activity"></td>
                                <td x-text="res.pax"></td>
                                <td>
                                    <span class="badge"
                                        :class="{
                                            'badge-declined': res.status === 'declined',
                                            'badge-expired': res.status === 'expired',
                                            'badge-done': res.status === 'done',
                                            'badge-cancelled': res.status === 'cancelled'
                                        }"
                                        x-text="res.status.charAt(0).toUpperCase() + res.status.slice(1)">
                                    </span>
                                    <span x-show="res.remarks" x-text="res.remarks" style="display:block; font-size:0.75rem; color:var(--color-danger); margin-top:0.25rem;"></span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem; align-items:center;">
                                        <!-- Hide button (only when not already hidden and not in Hidden filter) -->
                                        <button x-show="activeFilter !== 'hidden' && !hiddenIds.includes(res.id)"
                                                @click="hideReservation(res.id)"
                                                title="Hide this reservation"
                                                style="background:none; border:none; cursor:pointer; font-size:1.1rem; opacity:0.6;">
                                            👁️
                                        </button>
                                        <!-- Unhide button (only in Hidden filter) -->
                                        <button x-show="activeFilter === 'hidden' && hiddenIds.includes(res.id)"
                                                @click="unhideReservation(res.id)"
                                                title="Unhide"
                                                style="background:none; border:none; cursor:pointer; font-size:1.1rem; opacity:0.6;">
                                            🔓
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredReservations.length === 0">
                            <td colspan="8" style="text-align:center; padding:2rem; color:var(--color-muted);">No archived reservations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>