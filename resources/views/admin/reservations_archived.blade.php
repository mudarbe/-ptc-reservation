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
    detailModal: false,
    detailItem: null,
    activeFilter: 'all',
selectedDate: '',
    hiddenIds: [],
    reservations: {{ json_encode(
        $reservations->map(function($r) {
            return [
                'id' => $r->id,
                'professor' => $r->user->full_name,
                'room' => $r->room->name,
                'date' => $r->reservation_date->format('M d, Y'),
                'raw_date' => $r->reservation_date->format('Y-m-d'),
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
        if (this.activeFilter === 'all') {

    if(this.selectedDate){

        return visible.filter(r =>
            r.raw_date === this.selectedDate
        );

    }

    return visible;
}
        if (this.activeFilter === 'maintenance') {
            return visible.filter(r => {
                return r.status === 'cancelled' && r.remarks && (r.remarks.toLowerCase().includes('maintenance') || r.remarks.toLowerCase().includes('admin use'));
            });
        }
        let filtered =
    visible.filter(r => r.status === this.activeFilter);

if(this.selectedDate){

    filtered = filtered.filter(r =>
        r.raw_date === this.selectedDate
    );

}

return filtered;
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

            <div class="archive-filter-bar">

    <div class="archive-filter-left">

        <label>Filter by Reservation Date</label>

        <input type="date"
               x-model="selectedDate"
               class="archive-date-filter">

    </div>

    <button x-show="selectedDate"
            @click="selectedDate = ''"
            class="archive-clear-btn">

        Clear

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
                            <tr

@click="detailItem = {

    professor: res.professor,

    room: res.room,

    date: res.date,

    time_slot: res.time_slot,

    activity: res.activity,

    pax: res.pax,

    status: res.status,

    remarks: res.remarks

};

detailModal = true"

class="clickable-row"
>
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

    <!-- Archived Reservation Detail Modal -->

<div x-show="detailModal"
     class="modal-overlay"
     @click.self="detailModal = false">

    <div class="modal-box"
         style="max-width:500px;">

        <div class="modal-header">

            <h3>
                Reservation Details
            </h3>

            <button @click="detailModal = false"
                    class="modal-close">

                &times;

            </button>

        </div>

        <template x-if="detailItem">

    <div class="account-detail-modern">

        <!-- TOP -->

        <div class="account-detail-top">

            <div class="account-avatar">

                <span x-text="detailItem.professor.charAt(0).toUpperCase()"></span>

            </div>

            <div>

                <h2 x-text="detailItem.professor"></h2>

                <p>
                    Archived Reservation
                </p>

            </div>

        </div>

        <!-- DETAILS -->

        <div class="account-detail-grid">

            <div class="account-detail-item">

                <span>Room</span>

                <strong x-text="detailItem.room"></strong>

            </div>

            <div class="account-detail-item">

                <span>Date</span>

                <strong x-text="detailItem.date"></strong>

            </div>

            <div class="account-detail-item">

                <span>Time Slot</span>

                <strong x-text="detailItem.time_slot"></strong>

            </div>

            <div class="account-detail-item">

                <span>Activity</span>

                <strong x-text="detailItem.activity"></strong>

            </div>

            <div class="account-detail-item">

                <span>Pax</span>

                <strong x-text="detailItem.pax"></strong>

            </div>

            <div class="account-detail-item">

                <span>Status</span>

                <div class="account-status-pill">

                    <span x-text="detailItem.status"></span>

                </div>

            </div>

            <div class="account-detail-item"
                 x-show="detailItem.remarks">

                <span>Remarks</span>

                <strong x-text="detailItem.remarks"></strong>

            </div>

        </div>

    </div>

</template>

        <div class="modal-actions"
             style="margin-top:1.5rem;">

            <button @click="detailModal = false"
                    class="btn btn-outline">

                Close

            </button>

        </div>

    </div>

</div>

</body>
</html>