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

    <!-- TOTAL -->

    <div class="stat-card purple clickable-stat stat-modern"
         @click="activeTab = 'reservations'; window.reservationFilter = 'all';">

        <div class="stat-top">

            <div class="stat-icon total-icon">

    <svg width="24"
         height="24"
         viewBox="0 0 24 24"
         fill="none"
         xmlns="http://www.w3.org/2000/svg">

        <rect x="4"
              y="5"
              width="16"
              height="15"
              rx="2"
              stroke="currentColor"
              stroke-width="2"/>

        <path d="M8 3V7"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"/>

        <path d="M16 3V7"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"/>

        <path d="M4 10H20"
              stroke="currentColor"
              stroke-width="2"/>

    </svg>

</div>

            <div class="stat-wave"></div>

        </div>

        <h3>Total Reservations</h3>

        <p class="stat-number">
            {{ Auth::user()->reservations()->count() }}
        </p>

    </div>

    <!-- PENDING -->

    <div class="stat-card yellow clickable-stat stat-modern"
         @click="statsModal = true; statsReservations = pendingRes; statsTitle = 'Pending Reservations'">

        <div class="stat-top">

            <div class="stat-icon pending-icon">

    <svg width="24"
         height="24"
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

            <div class="stat-wave"></div>

        </div>

        <h3>Pending</h3>

        <p class="stat-number">
            {{ Auth::user()->reservations()->where('status', 'pending')->count() }}
        </p>

    </div>

    <!-- APPROVED -->

    <div class="stat-card green clickable-stat stat-modern"
         @click="statsModal = true; statsReservations = approvedOngoingRes; statsTitle = 'Approved / Ongoing'">

        <div class="stat-top">

            <div class="stat-icon approved-icon">

    <svg width="24"
         height="24"
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

            <div class="stat-wave"></div>

        </div>

        <h3>Approved / Ongoing</h3>

        <p class="stat-number">
            {{ Auth::user()->reservations()->whereIn('status', ['approved','ongoing'])->count() }}
        </p>

    </div>

</div>

                                    @php
                $lastRes = Auth::user()->reservations()->latest()->first();
            @endphp

                                       <!-- Reservation History (collapsible) -->
            @php
                $dashHistoryReservations = Auth::user()->reservations()
                    ->where('status', '!=', 'expired')
                    ->with('room')
                    ->orderBy('reservation_date', 'desc')
                    ->get();
            @endphp

            @if ($dashHistoryReservations->count() > 0)
                <div class="dash-history" x-data="{ expanded: false }">
                    <h4>Reservation History</h4>
                    <div class="dash-history-list">
                        @foreach ($dashHistoryReservations as $index => $res)
                            <div class="dash-history-item" style="{{ $index > 0 ? 'display: none;' : '' }}"
                                 :style="{{ $index > 0 ? 'expanded ? \'display: flex;\' : \'display: none;\'' : '' }}">
                                <span class="badge badge-{{ $res->status }}">{{ ucfirst($res->status) }}</span>
                                <span class="dash-history-room">{{ $res->room->name }}</span>
                                <span class="dash-history-date">{{ $res->reservation_date->format('M d, Y') }}</span>
                                <span class="dash-history-slot">{{ $res->time_slot }}</span>
                                <span class="dash-history-activity">{{ $res->activity_name }}</span>
                                <span class="dash-history-pax">({{ $res->pax }} pax)</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($dashHistoryReservations->count() > 1)
                        <div style="text-align: center; margin-top: 0.5rem;">
                            <button class="btn btn-outline btn-sm" @click="expanded = !expanded">
                                <span x-text="expanded ? '▲ Less' : '▼ More'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Dashboard Reservation Calendar -->
<div class="dashboard-calendar-card">

    <div class="dashboard-calendar-header">
        <div>
            <h2>Reservation Calendar</h2>
            <p>View all your submitted reservations and statuses.</p>
        </div>

        <div class="calendar-month-label">
            {{ now()->format('F Y') }}
        </div>
    </div>

    @php
        $currentMonth = now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $startDay = $startOfMonth->copy()->startOfWeek(Carbon\Carbon::SUNDAY);
        $endDay = $endOfMonth->copy()->endOfWeek(Carbon\Carbon::SATURDAY);

        $calendarDays = [];

        $date = $startDay->copy();

        while ($date <= $endDay) {
            $calendarDays[] = $date->copy();
            $date->addDay();
        }

        $dashboardCalendarReservations = Auth::user()->reservations()
            ->where('status', '!=', 'expired')
            ->with('room')
            ->get()
            ->groupBy(fn($r) => $r->reservation_date->toDateString());
    @endphp

    <div class="dashboard-calendar-grid">

        <div class="dashboard-day-name">Sun</div>
        <div class="dashboard-day-name">Mon</div>
        <div class="dashboard-day-name">Tue</div>
        <div class="dashboard-day-name">Wed</div>
        <div class="dashboard-day-name">Thu</div>
        <div class="dashboard-day-name">Fri</div>
        <div class="dashboard-day-name">Sat</div>

        @foreach($calendarDays as $day)

            @php
    $formattedDate = $day->toDateString();

    $isPastDate = $day->lt(now()->startOfDay());

    $isSunday = $day->dayOfWeek === Carbon\Carbon::SUNDAY;

    $isDisabled = $isPastDate || $isSunday;

    $dayReservations = $dashboardCalendarReservations->get($formattedDate, collect());

    $isCurrentMonth = $day->month === $currentMonth->month;

    $isToday = $day->isToday();
@endphp

<div class="dashboard-calendar-day
    {{ !$isCurrentMonth ? 'other-month' : '' }}
    {{ $isToday ? 'today' : '' }}
    {{ $isDisabled ? 'disabled-day' : '' }}"

    @if(!$isDisabled)
    @click="
        selectedDate = '{{ $formattedDate }}';
        showModal = true;
        step = 2;
        roomTypeSelected = false;
        selectedRoom = null;
        selectedTimeSlot = '';
        expandedRoom = null;
        loadRooms();

        reservationsForSelectedDate = {{ json_encode(
            $dayReservations->map(function($r) {
                return [
    'id' => $r->id,
    'room' => $r->room->name,
    'slot' => $r->time_slot,
    'status' => ucfirst($r->status),
    'activity' => $r->activity_name,
];
            })->values()
        ) }};
    "
    @endif
>

    <div class="dashboard-day-number">
        {{ $day->format('d') }}
    </div>

    <div class="dashboard-day-events">

        @foreach($dayReservations->take(3) as $reservation)

            <div class="dashboard-event-item">

                <span class="badge badge-{{ $reservation->status }}">
                    {{ ucfirst($reservation->status) }}
                </span>

                <div class="dashboard-event-room">
                    {{ $reservation->room->name }}
                </div>

                <div class="dashboard-event-time">
                    {{ $reservation->time_slot }}
                </div>

            </div>

        @endforeach

        @if($dayReservations->count() > 3)
            <div class="dashboard-more-events">
                +{{ $dayReservations->count() - 3 }} more
            </div>
        @endif

    </div>

</div>

        @endforeach

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
        <th>Room</th>
        <th>Date</th>
        <th>Time Slot</th>
        <th>Activity</th>
        <th>Pax</th>
        <th>Status</th>
    </tr>
</thead>
                                </thead>
                                <tbody>
                                    <template x-for="res in statsReservations" :key="res.id">

<tr
    class="clickable-row"
    style="cursor:pointer;"

    @click="
    statsModal = false;

    let targetFilter =
        (res.status === 'approved' || res.status === 'ongoing')
            ? 'approved_ongoing'
            : 'pending';

    window.dispatchEvent(
        new CustomEvent('jump-to-reservation', {
            detail: {
                id: res.id,
                filter: targetFilter
            }
        })
    );
"
>
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

<div id="reservation-showcase"
     class="reservation-showcase"
     x-data="{ expandedUpcoming: false }">

    {{-- UPCOMING RESERVATION --}}
    @if(!$upcoming->checked_in_at)

    <div class="reservation-mini-bar"
         @click="expandedUpcoming = !expandedUpcoming">

        <div class="mini-left">

            <div class="mini-icon">
                ❗
            </div>

            <div>
                <div class="mini-title">
                    Your Upcoming Reservation
                </div>

                <div class="mini-sub">
                    Click for details
                </div>
            </div>

        </div>

        <div class="mini-arrow"
             x-text="expandedUpcoming ? '▲' : '▼'">
        </div>

    </div>

    {{-- EXPANDED UPCOMING CARD --}}
    <div x-show="expandedUpcoming"
         x-transition
         class="reservation-expanded-card">

        <!-- LEFT SIDE -->
        <div class="expanded-left">

            <div class="expanded-room-icon">
                
            </div>

            <h2>
                {{ $upcoming->room->name }}
            </h2>

            <p>
                {{ $upcoming->activity_name }}
            </p>

            <div class="expanded-status-pill">
                Upcoming
            </div>

        </div>

        <!-- RIGHT SIDE -->
        <div class="expanded-right">

            <div class="expanded-grid">

                <div class="expanded-item">
                    <span>Date</span>
                    <strong>
                        {{ $upcoming->reservation_date->format('M d, Y') }}
                    </strong>
                </div>

                <div class="expanded-item">
                    <span>Time Slot</span>
                    <strong>
                        {{ $upcoming->time_slot }}
                    </strong>
                </div>

                <div class="expanded-item">
                    <span>Room Type</span>
                    <strong>
                        {{ ucfirst($upcoming->room->type) }}
                    </strong>
                </div>

                <div class="expanded-item">
                    <span>Pax</span>
                    <strong>
                        {{ $upcoming->pax }}
                    </strong>
                </div>

            </div>

            <div class="expanded-warning">
                ⏳ You can only mark as arrived once your reservation time starts.
            </div>

            <form action="/professor/reservations/{{ $upcoming->id }}/check-in"
                  method="POST">

                @csrf

                <button type="submit"
                        class="expanded-arrive-btn"
                        {{ now()->lt(
                            \Carbon\Carbon::parse(
                                $upcoming->reservation_date->toDateString() . ' ' .
                                parseSlotStartTime($upcoming->time_slot),
                                'Asia/Manila'
                            )
                        ) ? 'disabled' : '' }}>

                    ✅ Mark Arrived

                </button>

            </form>

        </div>

    </div>

    @else

    {{-- ONGOING RESERVATION FULL WIDTH --}}
    <div class="ongoing-full-card">

        <!-- LEFT TIMER -->
        <div class="ongoing-left">

            <div class="ongoing-label">
                🟢 Your Ongoing Reservation
            </div>

            <div class="ongoing-circle"
                 x-data="{ remaining: '' }"
                 x-init="
                    const endTime = new Date('{{ $slotEndCarbon->toDateTimeString() }}').getTime();

                    const update = () => {

                        const now = new Date().getTime();

                        const diff = endTime - now;

                        if(diff <= 0) {
                            remaining = 'Done';
                            return;
                        }

                        const hours = Math.floor(diff / 3600000);

                        const minutes = Math.floor((diff % 3600000) / 60000);

                        const seconds = Math.floor((diff % 60000) / 1000);

                        remaining =
                            hours + ':' +
                            minutes.toString().padStart(2,'0') + ':' +
                            seconds.toString().padStart(2,'0');
                    };

                    update();

                    setInterval(update, 1000);
                 ">

                <div class="ongoing-timer-text"
                     x-text="remaining">
                </div>

                <div class="ongoing-small-text">
                    Remaining Time
                </div>

            </div>

        </div>

        <!-- RIGHT DETAILS -->
        <div class="ongoing-right">

            <div class="ongoing-grid">

                <div class="ongoing-item">
                    <span>Room</span>
                    <strong>{{ $upcoming->room->name }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Time Slot</span>
                    <strong>{{ $upcoming->time_slot }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Activity</span>
                    <strong>{{ $upcoming->activity_name }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Room Type</span>
                    <strong>{{ ucfirst($upcoming->room->type) }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Date</span>
                    <strong>{{ $upcoming->reservation_date->format('M d, Y') }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Pax</span>
                    <strong>{{ $upcoming->pax }}</strong>
                </div>

            </div>

        </div>

    </div>

    @endif

</div>

@endif
        </div>

                <!-- My Reservations Tab -->
        <div
x-show="activeTab === 'reservations'"

@jump-to-reservation.window="
    activeTab = 'reservations';

    filter = $event.detail.filter;

    selectedReservationId = $event.detail.id;

    setTimeout(() => {

        const row =
            document.getElementById(
                'reservation-row-' + $event.detail.id
            );

        if(row){

            row.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

        }

    }, 350);
"

x-data="{
            filter: 'all',
            showFilters: false,
            showResModal: false,
            selectedReservation: null,
selectedReservationId: null,
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
                                <tr
    :id="'reservation-row-' + res.id"

    @click="
        selectedReservation = res;
        showResModal = true;
    "

    class="clickable-row"

    :class="{
        'highlighted-reservation':
            selectedReservationId === res.id
    }"
>
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

<div class="profile-ongoing-wrapper">

    <div class="ongoing-full-card profile-ongoing-card">

        <!-- LEFT SIDE -->

        <div class="ongoing-left">

            <div class="ongoing-label">

                {{ $profileReservation->status === 'ongoing'
                    ? '🟢 Ongoing Reservation'
                    : '📅 Upcoming Reservation' }}

            </div>

            @if($profileReservation->status === 'ongoing' && $profileSlotEnd)

                <div class="ongoing-circle"
                     x-data="{ remaining: '' }"
                     x-init="
                        const endTime = new Date('{{ $profileSlotEnd->toDateTimeString() }}').getTime();

                        const update = () => {

                            const now = new Date().getTime();

                            const diff = endTime - now;

                            if(diff <= 0) {
                                remaining = 'Done';
                                return;
                            }

                            const hours = Math.floor(diff / 3600000);

                            const minutes = Math.floor((diff % 3600000) / 60000);

                            const seconds = Math.floor((diff % 60000) / 1000);

                            remaining =
                                hours + ':' +
                                minutes.toString().padStart(2,'0') + ':' +
                                seconds.toString().padStart(2,'0');
                        };

                        update();

                        setInterval(update, 1000);
                     ">

                    <div class="ongoing-timer-text"
                         x-text="remaining">
                    </div>

                    <div class="ongoing-small-text">
                        Remaining Time
                    </div>

                </div>

            @else

                <div class="profile-upcoming-icon">
                    📅
                </div>

            @endif

        </div>

        <!-- RIGHT SIDE -->

        <div class="ongoing-right">

            <div class="ongoing-grid">

                <div class="ongoing-item">
                    <span>Room</span>
                    <strong>{{ $profileReservation->room->name }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Time Slot</span>
                    <strong>{{ $profileReservation->time_slot }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Activity</span>
                    <strong>{{ $profileReservation->activity_name }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Status</span>
                    <strong>{{ ucfirst($profileReservation->status) }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Date</span>
                    <strong>{{ $profileReservation->reservation_date->format('M d, Y') }}</strong>
                </div>

                <div class="ongoing-item">
                    <span>Pax</span>
                    <strong>{{ $profileReservation->pax }}</strong>
                </div>

                @if($profileReservation->checked_in_at)
                <div class="ongoing-item">
                    <span>Arrived At</span>
                    <strong>
                        {{ \Carbon\Carbon::parse($profileReservation->checked_in_at)->format('h:i A') }}
                    </strong>
                </div>
                @endif

            </div>

        </div>

    </div>

</div>

@else

<div class="card" style="flex: 1; min-width: 280px; background: #f8fafc;">
    <h2 class="card-header">📅 No Ongoing Reservation</h2>
    <p style="color: var(--prof-muted);">
        You have no active reservation at the moment.
    </p>
</div>

@endif

            </div>

            <!-- MIS SUPPORT BOX -->

            <div class="profile-support-box">

                <div class="profile-support-icon">
                    ?
                </div>

                <div class="profile-support-text">

                    <strong>Have problems?</strong>

                    <p>
                        Contact MIS:
                        <a href="mailto:mis@paterostechnologicalcollege.edu.ph">
                            mis@paterostechnologicalcollege.edu.ph
                        </a>
                    </p>

                </div>

            </div>

        </div>

<!-- Floating Reservation Shortcut -->

@if($upcoming)

<div class="floating-reservation-btn"
     @click="
        activeTab = 'dashboard';

        setTimeout(() => {
            document.getElementById('reservation-showcase')
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
        }, 120);
     ">

    <div class="floating-pill">

        @if($upcoming->checked_in_at)

            <span class="floating-status-dot ongoing"></span>

            <span>Ongoing</span>

        @else

            <span class="floating-status-dot upcoming"></span>

            <span>Upcoming</span>

        @endif

    </div>

</div>

@endif

    <div class="floating-pulse"></div>

    <svg width="24"
         height="24"
         viewBox="0 0 24 24"
         fill="none"
         xmlns="http://www.w3.org/2000/svg">

        <rect x="4"
              y="5"
              width="16"
              height="15"
              rx="2"
              stroke="currentColor"
              stroke-width="2"/>

        <path d="M8 3V7"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"/>

        <path d="M16 3V7"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"/>

        <path d="M4 10H20"
              stroke="currentColor"
              stroke-width="2"/>

    </svg>

</div>

    <!-- Booking Modal -->
    @include('professor.partials.booking_modal')

        

    <script>
    function professorApp() {
        return {
            activeTab: 'dashboard',
            reservationsForSelectedDate: [],
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