<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Professor Dashboard - PTC Reservation</title>
    <link rel="stylesheet" href="{{ asset('css/professor.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @include('partials.favicon')
</head>
<body x-data="professorApp()">

    <!-- Header -->
    <header class="professor-header">
        <div style="display:flex; align-items:center; gap:1rem;">
            <img src="{{ asset('images/ptc_logo.png') }}" alt="PTC Logo" class="header-logo">
            <h1>PTC Reservation · Professor</h1>
        </div>
        <div class="user-area">
    <img src="{{ asset('images/duck.png') }}" alt="Avatar" class="header-avatar">
    <span>Welcome, {{ Auth::user()->full_name }}</span>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn-logout">Logout</button>
    </form>
</div>
    </header>

    <!-- Tabs -->
    <div class="tab-nav">
        <nav>
            <button @click="activeTab = 'dashboard'" :class="{ 'active': activeTab === 'dashboard' }" class="tab-btn">Dashboard</button>
            <button @click="activeTab = 'reservations'" :class="{ 'active': activeTab === 'reservations' }" class="tab-btn">My Reservations</button>
            <button @click="activeTab = 'book'" :class="{ 'active': activeTab === 'book' }" class="tab-btn">Book Reservation</button>
            <button @click="activeTab = 'profile'" :class="{ 'active': activeTab === 'profile' }" class="tab-btn">Profile</button>
        </nav>
    </div>

    <!-- Content -->
    <main class="professor-content">

                <!-- Dashboard Tab -->
        <div x-show="activeTab === 'dashboard'" x-data="{
            statsModal: false,
            statsReservations: [],
            statsTitle: '',
            pendingRes: {{ json_encode(
                Auth::user()->reservations()
                    ->where('status', 'pending')
                    ->with('room')
                    ->latest()
                    ->get()
                    ->map(function($r) {
                        return [
                            'id' => $r->id,
                            'room_name' => $r->room->name,
                            'date' => $r->reservation_date->format('M d, Y'),
                            'time_slot' => $r->time_slot,
                            'activity' => $r->activity_name,
                            'pax' => $r->pax,
                            'status' => $r->status,
                        ];
                    })
            ) }},
            approvedOngoingRes: {{ json_encode(
                Auth::user()->reservations()
                    ->whereIn('status', ['approved','ongoing'])
                    ->with('room')
                    ->latest()
                    ->get()
                    ->map(function($r) {
                        return [
                            'id' => $r->id,
                            'room_name' => $r->room->name,
                            'date' => $r->reservation_date->format('M d, Y'),
                            'time_slot' => $r->time_slot,
                            'activity' => $r->activity_name,
                            'pax' => $r->pax,
                            'status' => $r->status,
                        ];
                    })
            ) }}
        }">
            <div style="display:flex; gap:1rem; margin-bottom:2rem;">
                <button @click="activeTab = 'book'" class="btn btn-success">+ Book New Reservation</button>
                <button @click="activeTab = 'reservations'" class="btn btn-primary">View My Reservations</button>
            </div>
            <div class="stats-grid">
                <div class="stat-card purple clickable-stat" @click="activeTab = 'reservations'; window.reservationFilter = 'all';">
                    <h3>Total Reservations</h3>
                    <p class="stat-number">{{ Auth::user()->reservations()->count() }}</p>
                </div>
                <div class="stat-card yellow clickable-stat" @click="statsModal = true; statsReservations = pendingRes; statsTitle = 'Pending Reservations'">
                    <h3>Pending</h3>
                    <p class="stat-number">{{ Auth::user()->reservations()->where('status', 'pending')->count() }}</p>
                </div>
                <div class="stat-card green clickable-stat" @click="statsModal = true; statsReservations = approvedOngoingRes; statsTitle = 'Approved / Ongoing'">
                    <h3>Approved / Ongoing</h3>
                    <p class="stat-number">{{ Auth::user()->reservations()->whereIn('status', ['approved','ongoing'])->count() }}</p>
                </div>
            </div>

            <!-- Stats Popup Modal (for Pending / Approved-Ongoing) -->
            <div x-show="statsModal" class="modal-overlay" @click.self="statsModal = false">
                <div class="modal-box" style="max-width: 700px;" @click.stop="">
                    <div class="modal-header">
                        <h3 x-text="statsTitle"></h3>
                        <button @click="statsModal = false" class="modal-close">&times;</button>
                    </div>
                    <template x-if="statsReservations.length > 0">
                        <div class="table-wrapper" style="max-height: 60vh; overflow-y: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Room</th><th>Date</th><th>Time Slot</th><th>Activity</th><th>Pax</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="res in statsReservations" :key="res.id">
                                        <tr>
                                            <td x-text="res.room_name"></td>
                                            <td x-text="res.date"></td>
                                            <td x-text="res.time_slot"></td>
                                            <td x-text="res.activity"></td>
                                            <td x-text="res.pax"></td>
                                            <td>
                                                <span class="badge"
                                                    :class="{
                                                        'badge-pending': res.status === 'pending',
                                                        'badge-approved': res.status === 'approved',
                                                        'badge-ongoing': res.status === 'ongoing',
                                                        'badge-done': res.status === 'done',
                                                        'badge-cancelled': res.status === 'cancelled',
                                                        'badge-declined': res.status === 'declined',
                                                        'badge-expired': res.status === 'expired'
                                                    }"
                                                    x-text="res.status.charAt(0).toUpperCase() + res.status.slice(1)">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="statsReservations.length === 0">
                        <div style="text-align:center; padding:2rem;">
                            <p style="color:var(--prof-muted); margin-bottom:1rem;">No reservations in this category yet.</p>
                            <button @click="activeTab = 'reservations'; statsModal = false" class="btn btn-primary">View My Reservations</button>
                        </div>
                    </template>
                </div>
            </div>

            @php
                $upcoming = Auth::user()->reservations()
                    ->whereIn('status', ['approved', 'ongoing'])
                    ->where('reservation_date', '>=', now()->toDateString())
                    ->with('room')
                    ->orderBy('reservation_date')
                    ->orderBy('time_slot')
                    ->first();

                $slotEndCarbon = null;
                if ($upcoming) {
                    $slotEndCarbon = \Carbon\Carbon::parse(
                        $upcoming->reservation_date->toDateString() . ' ' .
                        parseSlotEndTime($upcoming->time_slot),
                        'Asia/Manila'
                    );
                }
            @endphp

            @if($upcoming)
                <div class="upcoming-card" @click="upcomingModal = true; upcomingRes = {{ json_encode([
                    'id' => $upcoming->id,
                    'room_name' => $upcoming->room->name,
                    'room_type' => ucfirst($upcoming->room->type),
                    'date' => $upcoming->reservation_date->format('M d, Y'),
                    'time_slot' => $upcoming->time_slot,
                    'activity' => $upcoming->activity_name,
                    'pax' => $upcoming->pax,
                    'status' => $upcoming->status,
                    'checked_in_at' => $upcoming->checked_in_at ? \Carbon\Carbon::parse($upcoming->checked_in_at)->format('h:i A') : null,
                    'slot_start' => \Carbon\Carbon::parse($upcoming->reservation_date->toDateString() . ' ' . parseSlotStartTime($upcoming->time_slot), 'Asia/Manila')->format('H:i'),
                    'slot_end' => \Carbon\Carbon::parse($upcoming->reservation_date->toDateString() . ' ' . parseSlotEndTime($upcoming->time_slot), 'Asia/Manila')->format('H:i'),
                ]) }}">
                    <h4 class="upcoming-title">
                        @if($upcoming->checked_in_at)
                            🟢 Your Ongoing Reservation
                        @else
                             Your Upcoming Reservation
                        @endif
                    </h4>
                    <div class="upcoming-minimal">
                        <span class="upcoming-date-small">{{ $upcoming->reservation_date->format('M d, Y') }}</span>
                        <span class="upcoming-slot-small">{{ $upcoming->time_slot }}</span>
                        <span class="upcoming-arrow">→</span>
                    </div>
                    @if($upcoming->checked_in_at && $slotEndCarbon)
                        <div class="ongoing-timer" x-data="{ remaining: '' }" x-init="
                            const endTime = new Date('{{ $slotEndCarbon->toDateTimeString() }}').getTime();
                            const update = () => {
                                const now = new Date().getTime();
                                const diff = endTime - now;
                                if (diff <= 0) { remaining = 'Time up'; return; }
                                const hours = Math.floor(diff / 3600000);
                                const minutes = Math.floor((diff % 3600000) / 60000);
                                remaining = hours + 'h ' + minutes + 'm remaining';
                            };
                            update();
                            setInterval(update, 1000);
                        ">
                            <span class="big-timer" x-text="remaining"></span>
                        </div>
                    @endif
                    <div class="upcoming-click-hint">Tap for details & check‑in</div>
                </div>

                <!-- Check‑in Modal -->
                <div x-show="upcomingModal" class="modal-overlay" @click.self="upcomingModal = false">
                    <div class="modal-box modal-green" style="max-width: 500px;" @click.stop="">
                        <div class="modal-header">
                            <h3>📌 Reservation Details</h3>
                            <button @click="upcomingModal = false" class="modal-close">&times;</button>
                        </div>
                        <template x-if="upcomingRes">
                            <div>
                                <p><strong>Room:</strong> <span x-text="upcomingRes.room_name"></span> (<span x-text="upcomingRes.room_type"></span>)</p>
                                <p><strong>Date:</strong> <span x-text="upcomingRes.date"></span></p>
                                <p><strong>Time:</strong> <span x-text="upcomingRes.time_slot"></span></p>
                                <p><strong>Activity:</strong> <span x-text="upcomingRes.activity"></span></p>
                                <p><strong>Pax:</strong> <span x-text="upcomingRes.pax"></span></p>
                                <p><strong>Status:</strong> <span class="badge" :class="'badge-' + upcomingRes.status" x-text="upcomingRes.status"></span></p>
                                <div x-show="upcomingRes.checked_in_at">
                                    <p><strong>Checked in at:</strong> <span x-text="upcomingRes.checked_in_at"></span></p>
                                </div>

                                <!-- Mark as Arrived button -->
                                <div class="check-in-section" x-data="{ now: new Date() }" x-init="setInterval(() => { now = new Date() }, 1000)">
                                    <p class="check-in-hint" x-show="!upcomingRes.checked_in_at">
                                        <span x-text="new Date() >= new Date('{{ $upcoming->reservation_date->toDateString() }}T' + upcomingRes.slot_start) ? 'You can now mark arrival' : 'Button will unlock at ' + upcomingRes.slot_start"></span>
                                    </p>
                                    <form :action="'/professor/reservations/' + upcomingRes.id + '/check-in'" method="POST" @submit="upcomingModal = false">
                                        @csrf
                                        <button type="submit" class="btn btn-check-in" 
                                            :disabled="upcomingRes.checked_in_at || new Date() < new Date('{{ $upcoming->reservation_date->toDateString() }}T' + upcomingRes.slot_start)">
                                            <span x-text="upcomingRes.checked_in_at ? 'Already Arrived' : '✅ Mark as Arrived'"></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @endif
        </div>

                <!-- My Reservations Tab -->
        <div x-show="activeTab === 'reservations'" x-data="{
            filter: 'all',
            showFilters: false,
            showResModal: false,
            selectedReservation: null,
            hiddenIds: [],
            reservations: {{ json_encode(
                Auth::user()->reservations()
                    ->with('room')
                    ->latest()
                    ->get()
                    ->map(function($r) {
                        return [
                            'id' => $r->id,
                            'room_name' => $r->room->name,
                            'room_type' => ucfirst($r->room->type),
                            'date' => $r->reservation_date->format('M d, Y'),
                            'time_slot' => $r->time_slot,
                            'activity' => $r->activity_name,
                            'pax' => $r->pax,
                            'status' => $r->status,
                            'remarks' => $r->admin_remarks,
                            'hold_expires_at' => $r->hold_expires_at ? $r->hold_expires_at->toISOString() : null,
                        ];
                    })
            ) }},
            get filteredReservations() {
                if (this.filter === 'hidden') {
                    // show only hidden reservations (those in hiddenIds)
                    return this.reservations.filter(r => this.hiddenIds.includes(r.id));
                }
                // for all other filters, first exclude hidden reservations
                let visible = this.reservations.filter(r => !this.hiddenIds.includes(r.id));
                if (this.filter === 'all') return visible;
                if (this.filter === 'approved_ongoing') return visible.filter(r => ['approved','ongoing'].includes(r.status));
                return visible.filter(r => r.status === this.filter);
            },
            isHideable(status) {
                return ['expired', 'done', 'declined', 'cancelled'].includes(status);
            },
            hideReservation(id) {
                if (!this.hiddenIds.includes(id)) {
                    this.hiddenIds.push(id);
                }
            },
            unhideReservation(id) {
                this.hiddenIds = this.hiddenIds.filter(i => i !== id);
            }
        }"
        x-init="if(window.reservationFilter){ filter = window.reservationFilter; window.reservationFilter = null; }"
        @set-filter.window="filter = $event.detail.filter; activeTab = 'reservations';">
            <div class="card">
                <h2 class="card-header">My Reservations</h2>
                <div style="margin-bottom:1rem;">
                    <button @click="showFilters = !showFilters" class="filter-toggle-btn">
                        <span x-text="showFilters ? '<' : '>'"></span>
                    </button>
                    <span style="font-size:0.85rem; color:var(--prof-muted);">Filter reservations</span>
                    <div x-show="showFilters" style="margin-top:0.5rem;">
                        <button @click="filter = 'all'" :class="{'active': filter === 'all'}" class="filter-btn">All</button>
                        <button @click="filter = 'pending'" :class="{'active': filter === 'pending'}" class="filter-btn">Pending</button>
                        <button @click="filter = 'approved_ongoing'" :class="{'active': filter === 'approved_ongoing'}" class="filter-btn">Approved/Ongoing</button>
                        <button @click="filter = 'done'" :class="{'active': filter === 'done'}" class="filter-btn">Done</button>
                        <button @click="filter = 'cancelled'" :class="{'active': filter === 'cancelled'}" class="filter-btn">Cancelled</button>
                        <button @click="filter = 'declined'" :class="{'active': filter === 'declined'}" class="filter-btn">Declined</button>
                        <button @click="filter = 'expired'" :class="{'active': filter === 'expired'}" class="filter-btn">Expired</button>
                        <button @click="filter = 'hidden'" :class="{'active': filter === 'hidden'}" class="filter-btn">👁️ Hidden</button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room</th><th>Date</th><th>Time Slot</th><th>Activity</th><th>Pax</th><th>Status</th><th>Hold Timer</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="res in filteredReservations" :key="res.id">
                                <tr @click="selectedReservation = res; showResModal = true" class="clickable-row">
                                    <td x-text="res.room_name"></td>
                                    <td x-text="res.date"></td>
                                    <td x-text="res.time_slot"></td>
                                    <td x-text="res.activity"></td>
                                    <td x-text="res.pax"></td>
                                    <td>
                                        <span class="badge"
                                            :class="{
                                                'badge-pending': res.status === 'pending',
                                                'badge-approved': res.status === 'approved',
                                                'badge-ongoing': res.status === 'ongoing',
                                                'badge-done': res.status === 'done',
                                                'badge-cancelled': res.status === 'cancelled',
                                                'badge-declined': res.status === 'declined',
                                                'badge-expired': res.status === 'expired'
                                            }"
                                            x-text="res.status.charAt(0).toUpperCase() + res.status.slice(1)">
                                        </span>
                                        <span x-show="res.remarks" x-text="res.remarks" style="display:block; font-size:0.75rem; color:var(--prof-danger); margin-top:0.25rem;"></span>
                                    </td>
                                    <td>
                                        <div x-show="res.status === 'pending' && res.hold_expires_at"
                                             x-data="{ expireAt: new Date(res.hold_expires_at), now: new Date(), timer: '' }"
                                             x-init="
                                                const update = () => {
                                                    now = new Date();
                                                    const diff = expireAt - now;
                                                    if (diff <= 0) { timer = 'Expired'; return; }
                                                    const mins = Math.floor(diff / 60000);
                                                    const secs = Math.floor((diff % 60000) / 1000);
                                                    timer = mins + ':' + secs.toString().padStart(2, '0');
                                                };
                                                update();
                                                setInterval(update, 1000);
                                             ">
                                            <span class="hold-timer" x-text="timer"></span>
                                        </div>
                                    </td>
                                    <td @click.stop="">
                                        <div style="display:flex; gap:0.5rem; align-items:center;">
                                            <!-- Hide button for hideable statuses -->
                                            <button x-show="isHideable(res.status) && !hiddenIds.includes(res.id)"
                                                    @click="hideReservation(res.id)"
                                                    title="Hide this reservation"
                                                    style="background:none; border:none; cursor:pointer; font-size:1.1rem; opacity:0.6;"
                                                    class="hover:opacity-100">
                                                👁️
                                            </button>
                                            <!-- Unhide button (visible in Hidden filter) -->
                                            <button x-show="filter === 'hidden' && hiddenIds.includes(res.id)"
                                                    @click="unhideReservation(res.id)"
                                                    title="Unhide"
                                                    style="background:none; border:none; cursor:pointer; font-size:1.1rem; opacity:0.6;"
                                                    class="hover:opacity-100">
                                                🔓
                                            </button>
                                            <!-- Cancel for pending -->
                                            <form x-show="res.status === 'pending'" :action="'/professor/reservations/' + res.id + '/cancel'" method="POST" onsubmit="return confirm('Cancel this reservation?');">
                                                @csrf
                                                <button type="submit" class="btn" style="background:var(--prof-danger); color:#fff; padding:0.3rem 0.6rem; font-size:0.8rem;">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredReservations.length === 0">
                                <td colspan="8" style="text-align:center; padding:2rem; color:var(--prof-muted);">No reservations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Reservation Detail Modal -->
                <div x-show="showResModal" class="modal-overlay" @click.self="showResModal = false">
                    <div class="modal-box" @click.stop="">
                        <div class="modal-header">
                            <h3>Reservation Details</h3>
                            <button @click="showResModal = false" class="modal-close">&times;</button>
                        </div>
                        <template x-if="selectedReservation">
                            <div>
                                <p><strong>Room:</strong> <span x-text="selectedReservation.room_name"></span> (<span x-text="selectedReservation.room_type"></span>)</p>
                                <p><strong>Date:</strong> <span x-text="selectedReservation.date"></span></p>
                                <p><strong>Time Slot:</strong> <span x-text="selectedReservation.time_slot"></span></p>
                                <p><strong>Activity:</strong> <span x-text="selectedReservation.activity"></span></p>
                                <p><strong>Pax:</strong> <span x-text="selectedReservation.pax"></span></p>
                                <p><strong>Status:</strong> <span class="badge" :class="'badge-' + selectedReservation.status" x-text="selectedReservation.status.charAt(0).toUpperCase() + selectedReservation.status.slice(1)"></span></p>
                                <p x-show="selectedReservation.remarks"><strong>Remarks:</strong> <span x-text="selectedReservation.remarks"></span></p>
                            </div>
                        </template>
                        <div class="modal-actions" style="margin-top:1.5rem;">
                            <button @click="showResModal = false" class="btn btn-outline">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

               <!-- Book Reservation Tab -->
        <div x-show="activeTab === 'book'">
            <div class="card">
                <h2 class="card-header">Book a Reservation</h2>
                <p style="color:var(--prof-muted); margin-bottom:1.5rem;">Start by selecting a date.</p>
                <button @click="openBookingModal" class="btn btn-success">+ Start Booking</button>

                @php
                    $lastRes = Auth::user()->reservations()->latest()->first();
                @endphp

                @if($lastRes)
                    <div class="recent-reservation-preview">
                        <h4>📋 Your Last Reservation</h4>
                        <p><strong>Room:</strong> {{ $lastRes->room->name }}</p>
                        <p><strong>Date:</strong> {{ $lastRes->reservation_date->format('M d, Y') }}</p>
                        <p><strong>Time:</strong> {{ $lastRes->time_slot }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge badge-{{ $lastRes->status }}">{{ ucfirst($lastRes->status) }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>

                                <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'">
            <div class="profile-cards" style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <!-- Left: Profile Information Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2 class="card-header">Profile Information</h2>

                    <div style="text-align: center;">
                        <img src="{{ asset('images/duck.png') }}" alt="Professor Avatar" class="profile-picture">
                    </div>

                    <div class="profile-field">
                        <label>Full Name</label>
                        <div class="field-value">{{ Auth::user()->full_name }}</div>
                    </div>
                    <div class="profile-field">
                        <label>Role</label>
                        <div class="field-value">{{ ucfirst(Auth::user()->role) }}</div>
                    </div>
                    <div class="profile-field">
                        <label>Personal Email</label>
                        <div class="field-value">
                            <span x-text="showPersonalEmail ? '{{ Auth::user()->personal_email }}' : '{{ substr(Auth::user()->personal_email, 0, 3) }}****@****.com'"></span>
                            <button @click="showPersonalEmail = !showPersonalEmail" class="toggle-view" x-text="showPersonalEmail ? 'Hide' : 'Show'"></button>
                        </div>
                    </div>
                    <div class="profile-field">
                        <label>Institutional Email</label>
                        <div class="field-value">
                            <span x-text="showInstitutionalEmail ? '{{ Auth::user()->institutional_email }}' : '{{ substr(Auth::user()->institutional_email, 0, 5) }}****@paterostechnologicalcollege.edu.ph'"></span>
                            <button @click="showInstitutionalEmail = !showInstitutionalEmail" class="toggle-view" x-text="showInstitutionalEmail ? 'Hide' : 'Show'"></button>
                        </div>
                    </div>
                </div>

                <!-- Right: Reservation Mini Card -->
                @php
                    // Look for a reservation that is either upcoming or ongoing today
                    $profileReservation = Auth::user()->reservations()
                        ->whereIn('status', ['approved', 'ongoing'])
                        ->where('reservation_date', now()->toDateString())
                        ->with('room')
                        ->orderBy('time_slot')
                        ->first();

                    $profileSlotEnd = null;
                    if ($profileReservation) {
                        $profileSlotEnd = \Carbon\Carbon::parse(
                            $profileReservation->reservation_date->toDateString() . ' ' .
                            parseSlotEndTime($profileReservation->time_slot),
                            'Asia/Manila'
                        );
                    }
                @endphp

                @if($profileReservation)
                    <div class="card card-gold profile-reservation-card" style="flex: 1; min-width: 280px; color: #fff;">
                        <h2 class="card-header" style="color: #fff;">
                            {{ $profileReservation->status === 'ongoing' ? '🟢 Ongoing Reservation' : '📅 Upcoming Reservation' }}
                        </h2>
                        <p><strong>Room:</strong> {{ $profileReservation->room->name }}</p>
                        <p><strong>Time Slot:</strong> {{ $profileReservation->time_slot }}</p>
                        <p><strong>Activity:</strong> {{ $profileReservation->activity_name }}</p>
                        <p><strong>Status:</strong> {{ ucfirst($profileReservation->status) }}</p>
                        @if($profileReservation->checked_in_at)
                            <p><strong>Arrived at:</strong> {{ \Carbon\Carbon::parse($profileReservation->checked_in_at)->format('h:i A') }}</p>
                        @endif

                        {{-- Timer only when the reservation is actually ongoing --}}
                        @if($profileReservation->status === 'ongoing' && $profileSlotEnd)
                            <div class="ongoing-timer" x-data="{ remaining: '' }" x-init="
                                const endTime = new Date('{{ $profileSlotEnd->toDateTimeString() }}').getTime();
                                const update = () => {
                                    const now = new Date().getTime();
                                    const diff = endTime - now;
                                    if (diff <= 0) { remaining = 'Time up'; return; }
                                    const hours = Math.floor(diff / 3600000);
                                    const minutes = Math.floor((diff % 3600000) / 60000);
                                    remaining = hours + 'h ' + minutes + 'm';
                                };
                                update();
                                setInterval(update, 1000);
                            ">
                                <span class="big-timer" x-text="remaining"></span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="card" style="flex: 1; min-width: 280px; background: #f8fafc;">
                        <h2 class="card-header">📅 No Ongoing Reservation</h2>
                        <p style="color: var(--prof-muted);">You have no active reservation at the moment.</p>
                    </div>
                @endif
            </div>
        </div>

    <!-- Booking Modal -->
    @include('professor.partials.booking_modal')

    <!-- Footer -->
    <footer class="professor-footer">
        Pateros Technological College &copy; {{ date('Y') }}
    </footer>

    <script>
    function professorApp() {
        return {
            activeTab: 'dashboard',
            showPersonalEmail: false,
            showInstitutionalEmail: false,
            showModal: false,
            upcomingModal: false,
            upcomingRes: null,
            step: 1,
            selectedDate: '',
            selectedRoom: null,
            selectedTimeSlot: '',
            activityName: '',
            pax: '',
            availableRooms: [],
            filteredRooms: [],
            roomTypeSelected: false,
            selectedRoomType: '',
            expandedRoom: null,
            timeSlots: [],
            loading: false,

            openBookingModal() {
                this.showModal = true;
                this.step = 1;
                this.selectedDate = '';
                this.selectedRoom = null;
                this.selectedTimeSlot = '';
                this.activityName = '';
                this.pax = '';
                this.availableRooms = [];
                this.filteredRooms = [];
                this.roomTypeSelected = false;
                this.selectedRoomType = '';
                this.expandedRoom = null;
                this.timeSlots = [];
                this.loading = false;
            },

            closeModal() {
                this.showModal = false;
            },

            initDatePicker() {
                const today = new Date().toISOString().split('T')[0];
                flatpickr("#datepicker", {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    disable: [function(date) { return date.getDay() === 0; }],
                    onChange: (selectedDates, dateStr) => {
                        this.selectedDate = dateStr;
                        this.step = 2;
                        this.roomTypeSelected = false;
                        this.filteredRooms = [];
                        this.expandedRoom = null;
                        this.loadRooms();
                    }
                });
            },

            filterByType(type) {
                this.selectedRoomType = type;
                this.filteredRooms = this.availableRooms.filter(r => r.type === type);
                this.roomTypeSelected = true;
                this.expandedRoom = null;
            },

            loadRooms() {
                this.loading = true;
                fetch(`/api/available-rooms?date=${this.selectedDate}`)
                    .then(res => res.json())
                    .then(data => {
                        this.availableRooms = data;
                        this.filteredRooms = [];
                        this.roomTypeSelected = false;
                        this.expandedRoom = null;
                        this.loading = false;
                    });
            },

            // Toggle the inline time‑slot list for a room
            toggleRoomTimeSlots(roomId) {
                this.expandedRoom = (this.expandedRoom === roomId) ? null : roomId;
            },

            // When a time slot is clicked, store the room & slot and go to details
            selectSlotAndProceed(room, slot) {
                this.selectedRoom = room;
                this.selectedTimeSlot = slot;
                this.step = 3;
            },

            submitBooking() {
                this.loading = true;
                fetch('/api/reservations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        date: this.selectedDate,
                        room_id: this.selectedRoom.id,
                        time_slot: this.selectedTimeSlot,
                        activity_name: this.activityName,
                        pax: this.pax
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success) {
                        alert('Reservation request submitted!');
                        this.closeModal();
                        location.reload();
                    } else {
                        alert(data.message || 'Error submitting reservation.');
                    }
                });
            },

            goBack() {
                if (this.step === 2 && this.roomTypeSelected) {
                    // If they haven't chosen a slot yet, let them go back to type selection
                    if (!this.selectedTimeSlot) {
                        this.roomTypeSelected = false;
                        this.filteredRooms = [];
                        this.expandedRoom = null;
                        return;
                    }
                }
                if (this.step > 1) this.step--;
            }
        }
    }
</script>
</body>
</html>