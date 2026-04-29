<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Reservations - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    detailModal: false,
    detailItem: null,
    declineModal: false,
    selectedDeclineRes: null,
    declineReasonType: 'schedule_conflict',
    declineReasonNotes: ''
}">

    <!-- Sidebar (unchanged) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>MIS Admin</h2>
            <p>PTC Reservation</p>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.account_requests') }}">Account Requests</a>
            <a href="{{ route('admin.reservations.pending') }}" class="active">Reservation Requests</a>
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
            <h1>Pending Reservation Requests</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <!-- Table -->
            @if($reservations->count() > 0)
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
                                <th>Hold Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $res)
                            <tr class="clickable-row"
                                @click="detailItem = {{ json_encode([
                                    'professor' => $res->user->full_name,
                                    'room' => $res->room->name,
                                    'date' => $res->reservation_date->format('M d, Y'),
                                    'time_slot' => $res->time_slot,
                                    'activity' => $res->activity_name,
                                    'pax' => $res->pax,
                                    'hold' => $res->hold_expires_at ? $res->hold_expires_at->format('M d, Y H:i') : 'N/A',
                                    'status' => ucfirst($res->status),
                                ]) }}; detailModal = true">
                                <td>{{ $res->user->full_name }}</td>
                                <td>{{ $res->room->name }}</td>
                                <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                                <td>{{ $res->time_slot }}</td>
                                <td>{{ $res->activity_name }}</td>
                                <td>{{ $res->pax }}</td>
                                <td>
                                    @if($res->hold_expires_at)
                                        <span class="hold-expires {{ $res->hold_expires_at->isPast() ? 'expired' : 'active' }}">
                                            {{ $res->hold_expires_at->diffForHumans() }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td @click.stop="">
                                    <div style="display:flex; gap:0.5rem;">
                                        <form action="{{ route('admin.reservations.approve', $res->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-sm btn-approve">Approve</button>
                                        </form>
                                        <!-- Decline button now opens modal -->
                                        <button type="button" class="btn-sm btn-decline"
                                                @click="selectedDeclineRes = {{ $res->id }}; declineModal = true; declineReasonType = 'schedule_conflict'; declineReasonNotes = ''">
                                            Decline
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-wrapper">
                    <div class="empty-state">No pending reservation requests.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal (unchanged) -->
    <div x-show="detailModal" class="modal-overlay" @click.self="detailModal = false">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Reservation Details</h3>
                <button @click="detailModal = false" class="modal-close">&times;</button>
            </div>
            <template x-if="detailItem">
                <div>
                    <p><strong>Professor:</strong> <span x-text="detailItem.professor"></span></p>
                    <p><strong>Room:</strong> <span x-text="detailItem.room"></span></p>
                    <p><strong>Date:</strong> <span x-text="detailItem.date"></span></p>
                    <p><strong>Time Slot:</strong> <span x-text="detailItem.time_slot"></span></p>
                    <p><strong>Activity:</strong> <span x-text="detailItem.activity"></span></p>
                    <p><strong>Pax:</strong> <span x-text="detailItem.pax"></span></p>
                    <p><strong>Hold Expires:</strong> <span x-text="detailItem.hold"></span></p>
                    <p><strong>Status:</strong> <span x-text="detailItem.status"></span></p>
                </div>
            </template>
            <div class="modal-actions" style="margin-top: 1.5rem;">
                <button @click="detailModal = false" class="btn btn-outline">Close</button>
            </div>
        </div>
    </div>

    <!-- DECLINE MODAL -->
    <div x-show="declineModal" class="modal-overlay" @click.self="declineModal = false">
        <div class="modal-box" style="max-width: 480px;">
            <h3>Decline Reservation</h3>
            <form :action="'/admin/reservations/' + selectedDeclineRes + '/decline'" method="POST">
                @csrf
                <label>Reason</label>
                <select name="reason_type" x-model="declineReasonType" required>
                    <option value="schedule_conflict">Schedule Conflict</option>
                    <option value="duplicate">Duplicate Booking</option>
                    <option value="invalid">Invalid Request</option>
                    <option value="other">Other</option>
                </select>
                <div x-show="declineReasonType === 'other'">
                    <label>Specify Reason</label>
                    <textarea name="reason_notes" x-model="declineReasonNotes" rows="2"></textarea>
                </div>
                <div class="note">An email will be automatically sent to the professor.</div>
                <div class="modal-actions">
                    <button type="button" @click="declineModal = false" class="btn-close">Cancel</button>
                    <button type="submit" class="btn-sm btn-decline">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>