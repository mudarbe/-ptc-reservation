<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $room->name }} Calendar - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

@php
    $timeSlots = $room->type === 'laboratory' 
        ? ['7-12 pm', '12-3 pm', '4-9 pm'] 
        : ['7-10 am', '10-1 pm', '1-4 pm', '4-7 pm'];
@endphp

<body x-data="{
    blockModal: false,
    selectedDate: '',
    selectedSlot: '',
    blockType: 'maintenance',
    blockNotes: '',
    showBlockedList: false,
    availableTimeSlots: {{ json_encode($timeSlots) }},

    // Confirmation flow
    conflictData: null,
    showConfirmation: false,
    submiting: false,

    updateAvailableSlots() {
        if (!this.selectedDate) {
            this.availableTimeSlots = {{ json_encode($timeSlots) }};
            this.selectedSlot = '';
            return;
        }
        const today = '{{ now()->toDateString() }}';
        if (this.selectedDate !== today) {
            this.availableTimeSlots = {{ json_encode($timeSlots) }};
            this.selectedSlot = '';
            return;
        }
        const now = new Date();
        this.availableTimeSlots = {{ json_encode($timeSlots) }}.filter(slot => {
            const end = new Date(this.selectedDate + 'T' + this.getSlotEnd(slot) + ':00');
            return now < end;
        });
        this.selectedSlot = '';
    },

    getSlotEnd(slot) {
        const map = {
            @foreach($timeSlots as $slot)
                '{{ $slot }}': '{{ parseSlotEndTime($slot) }}',
            @endforeach
        };
        return map[slot] || '00:00';
    },

    // Check for conflicts before submitting
    checkAndSubmit() {
        if (!this.selectedDate || !this.selectedSlot) {
            alert('Please select a date and time slot.');
            return;
        }
        const url = new URL('/api/admin/check-slot-conflict', window.location.origin);
        url.searchParams.set('room_id', '{{ $room->id }}');
        url.searchParams.set('date', this.selectedDate);
        url.searchParams.set('time_slot', this.selectedSlot);

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.conflict) {
                    this.conflictData = data.reservation;
                    this.showConfirmation = true;
                } else {
                    this.submitBlockForm();
                }
            });
    },

    // Actually submit the block form
    submitBlockForm() {
        this.submiting = true;
        const form = document.getElementById('blockForm');
        if (form) {
            form.submit();
        }
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
            <a href="{{ route('admin.reservations.archived') }}">Archived</a>
            <a href="{{ route('admin.users') }}">Manage Users</a>
            <a href="{{ route('admin.rooms.index') }}" class="active">Rooms</a>
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
            <div>
                <h1>{{ $room->name }} - Booking Calendar</h1>
                <span style="color:var(--color-muted); font-size:0.9rem;">Type: {{ ucfirst($room->type) }} | Capacity: {{ $room->capacity }}</span>
            </div>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            <a href="{{ route('admin.rooms.index') }}" style="display:inline-block; margin-bottom:1rem; color:var(--color-primary); text-decoration:none;">← Back to Rooms</a>

            <!-- Action Buttons -->
            <div style="margin-bottom:1rem; display:flex; gap:0.5rem;">
                <button @click="blockModal = true; updateAvailableSlots()" class="btn btn-warning" style="padding:0.5rem 1rem;">+ Set Maintenance / Admin Use</button>
                <button @click="showBlockedList = !showBlockedList" class="btn btn-primary" style="padding:0.5rem 1rem; background:#475569;">
                    <span x-text="showBlockedList ? 'Hide' : 'Show'"></span> Blocked Slots
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <!-- Blocked Slots List -->
            <div x-show="showBlockedList" class="blocked-panel">
                <h3>Currently Blocked Slots</h3>
                @php
                    $blockedSlotsList = \App\Models\BlockedSlot::where('room_id', $room->id)
                        ->orderBy('date')
                        ->orderBy('time_slot')
                        ->get();
                @endphp
                @if($blockedSlotsList->count() > 0)
                    @foreach($blockedSlotsList as $block)
                        <div class="blocked-item">
                            <div class="blocked-info">
                                <span class="blocked-date">{{ $block->date->format('M d, Y') }}</span> - 
                                <span class="blocked-slot">{{ $block->time_slot }}</span>
                                <span class="blocked-type-badge">{{ ucfirst(str_replace('_', ' ', $block->type)) }}</span>
                                @if($block->notes)
                                    <p style="font-size:0.8rem; color:var(--color-muted);">{{ $block->notes }}</p>
                                @endif
                            </div>
                            <form action="{{ route('admin.rooms.unblock', $room->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="date" value="{{ $block->date->toDateString() }}">
                                <input type="hidden" name="time_slot" value="{{ $block->time_slot }}">
                                <button type="submit" class="unblock-btn">Unblock</button>
                            </form>
                        </div>
                    @endforeach
                @else
                    <p style="color:var(--color-muted);">No blocked slots.</p>
                @endif
            </div>

            <!-- Calendar Table -->
            @php
                $reservations = \App\Models\Reservation::where('room_id', $room->id)
                    ->whereIn('status', ['approved', 'ongoing', 'done'])
                    ->with('user')
                    ->orderBy('reservation_date')
                    ->get()
                    ->groupBy(fn($res) => $res->reservation_date->toDateString())
                    ->map(fn($group) => $group->keyBy('time_slot'));

                $blockedSlots = \App\Models\BlockedSlot::where('room_id', $room->id)
                    ->orderBy('date')
                    ->get()
                    ->groupBy(fn($block) => $block->date->toDateString())
                    ->map(fn($group) => $group->keyBy('time_slot'));

                $startDate = now()->startOfDay();
                $endDate = now()->addDays(14)->endOfDay();
                $now = now()->setTimezone('Asia/Manila');
            @endphp

            <div class="calendar-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            @foreach($timeSlots as $slot)
                                <th>{{ $slot }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($date = $startDate->copy(); $date->lte($endDate); $date->addDay())
                            @php $dateStr = $date->toDateString(); @endphp
                            <tr>
                                <td>
                                    {{ $date->format('M d') }}
                                    @if($date->isToday()) <span style="color:var(--color-primary); font-weight:600;">(Today)</span> @endif
                                </td>
                                @foreach($timeSlots as $slot)
                                    @php
                                        $res = $reservations[$dateStr][$slot] ?? null;
                                        $block = $blockedSlots[$dateStr][$slot] ?? null;

                                        // Determine if the slot is already over
                                        $slotEndTime = parseSlotEndTime($slot);
                                        list($eh, $em) = explode(':', $slotEndTime);
                                        $slotEndCarbon = \Carbon\Carbon::parse($dateStr, 'Asia/Manila')->setTime($eh, $em, 0);
                                        $isPast = $now->greaterThanOrEqualTo($slotEndCarbon);
                                    @endphp
                                    <td>
                                        @if($block)
                                            <div class="cell-block">
                                                <div style="font-weight:600;">{{ ucfirst(str_replace('_', ' ', $block->type)) }}</div>
                                                @if($block->notes)
                                                    <div style="font-size:0.7rem; color:#475569;">{{ $block->notes }}</div>
                                                @endif
                                            </div>
                                        @elseif($res)
                                            <div class="cell-reservation">
                                                <div class="prof-name">{{ $res->user->full_name }}</div>
                                                <div class="prof-activity">{{ $res->activity_name }}</div>
                                                <div class="prof-pax">({{ $res->pax }} pax)</div>
                                                <span class="badge badge-approved">{{ ucfirst($res->status) }}</span>
                                            </div>
                                        @else
                                            @if($isPast)
                                                <span class="cell-empty" style="font-weight:500; color:#94a3b8;">Past</span>
                                            @else
                                                <button 
                                                    @click="blockModal = true; selectedDate = '{{ $dateStr }}'; selectedSlot = '{{ $slot }}'; updateAvailableSlots()"
                                                    class="block-link">
                                                    Block
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

                        <!-- Block Modal -->
            <div x-show="blockModal" class="modal-overlay" style="display:none;" @click.self="blockModal = false">
                <div class="modal-box">
                    <!-- Main form (always present in DOM, hidden with x-show) -->
                    <div x-show="!showConfirmation">
                        <h3>Block Time Slot</h3>
                        <form id="blockForm" action="{{ route('admin.rooms.block', $room->id) }}" method="POST">
                            @csrf
                            <label>Date</label>
                            <input type="date" name="date" x-model="selectedDate" required
                                   min="{{ now()->toDateString() }}"
                                   @change="updateAvailableSlots()">

                            <label>Time Slot</label>
                            <select name="time_slot" x-model="selectedSlot" required>
                                <option value="">Select</option>
                                <template x-for="slot in availableTimeSlots" :key="slot">
                                    <option :value="slot" x-text="slot"></option>
                                </template>
                            </select>

                            <label>Type</label>
                            <select name="type" x-model="blockType" required>
                                <option value="maintenance">Maintenance</option>
                                <option value="admin_use">Admin Use</option>
                            </select>

                            <label>Notes (optional)</label>
                            <textarea name="notes" x-model="blockNotes" rows="2"></textarea>

                            <div class="modal-actions">
                                <button type="button" @click="blockModal = false" class="btn-close">Cancel</button>
                                <button type="button" class="btn-sm btn-decline"
                                        @click="checkAndSubmit()" :disabled="submiting">
                                    Block Slot
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Confirmation screen (form still in DOM above, just hidden) -->
                    <div x-show="showConfirmation">
                        <h3 style="color:#b91c1c;">⚠️ Warning</h3>
                        <p>This time slot has an approved reservation:</p>
                        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:1rem; margin:1rem 0;">
                            <p><strong>Professor:</strong> <span x-text="conflictData?.professor"></span></p>
                            <p><strong>Activity:</strong> <span x-text="conflictData?.activity"></span></p>
                            <p><strong>Date:</strong> <span x-text="selectedDate"></span></p>
                            <p><strong>Time Slot:</strong> <span x-text="selectedSlot"></span></p>
                        </div>
                        <p style="color:#991b1b;">If you proceed, this reservation will be cancelled and the professor will be notified.</p>
                        <div class="modal-actions">
                            <button type="button" @click="showConfirmation = false; conflictData = null" class="btn-close">Cancel</button>
                            <button type="button" class="btn-sm btn-decline"
                                    @click="submitBlockForm()" :disabled="submiting">
                                Proceed & Cancel Reservation
                            </button>
                        </div>
                    </div>
                </div>
            </div>

</body>
</html>