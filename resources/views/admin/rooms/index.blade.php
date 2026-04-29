<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Management - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>

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
            <h1>Room Management</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <!-- Add Room Form -->
            <div class="form-panel">
                <h2>Add New Room</h2>
                <form action="{{ route('admin.rooms.store') }}" method="POST" class="form-grid">
                    @csrf
                    <div>
                        <label>Room Name</label>
                        <input type="text" name="name" placeholder="e.g., Room 305" required>
                    </div>
                    <div>
                        <label>Type</label>
                        <select name="type" required>
                            <option value="laboratory">Laboratory</option>
                            <option value="lecture">Lecture</option>
                        </select>
                    </div>
                    <div>
                        <label>Capacity</label>
                        <input type="number" name="capacity" min="1" value="30" required>
                    </div>
                    <div>
                        <button type="submit" class="btn-add">Add Room</button>
                    </div>
                </form>
                <p class="form-note">
                    * Laboratory: 7-12 pm, 12-3 pm, 4-9 pm<br>
                    * Lecture: 7-10 am, 10-1 pm, 1-4 pm, 4-7 pm
                </p>
            </div>

            <!-- Rooms List -->
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Time Slots</th>
                            <th>Active Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rooms as $room)
                        <tr>
                            <td style="font-weight:600;">{{ $room->name }}</td>
                            <td style="text-transform:capitalize;">{{ $room->type }}</td>
                            <td>{{ $room->capacity }}</td>
                            <td style="font-size:0.85rem; color:#475569;">
                                @if($room->type === 'laboratory')
                                    7-12 pm, 12-3 pm, 4-9 pm
                                @else
                                    7-10 am, 10-1 pm, 1-4 pm, 4-7 pm
                                @endif
                            </td>
                            <td>{{ $room->active_bookings_count ?? 0 }}</td>
                            <td>
                                <span class="badge {{ $room->is_active ? 'badge-approved' : 'badge-declined' }}">
                                    {{ $room->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="room-actions">
                                    <a href="{{ route('admin.rooms.show', $room->id) }}" class="view-link">View Calendar</a>
                                    @if(($room->active_bookings_count ?? 0) > 0)
                                        <span class="disabled-text" title="Cannot deactivate room with active bookings">Deactivate</span>
                                    @else
                                        <form action="{{ route('admin.rooms.toggle', $room->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="deactivate-link" style="border:none; background:none; padding:0;">
                                                {{ $room->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($rooms) === 0)
                    <div class="empty-state">No rooms found.</div>
                @endif
            </div>
        </div>
    </div>

</body>
</html>