<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    usersPopup: false,
    allUsers: [],
    usersLoading: false,

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
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.account_requests') }}">Account Requests</a>
            <a href="{{ route('admin.reservations.pending') }}">Reservation Requests</a>
            <a href="{{ route('admin.reservations.active') }}">Active Reservations</a>
            <a href="{{ route('admin.reservations.archived') }}">Archived</a>
            <a href="{{ route('admin.users') }}" class="active">Manage Users</a>
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
            <h1>Manage Users</h1>
            <span class="user-info">{{ Auth::user()->full_name }}</span>
        </header>

        <div class="content">
            <!-- Search Form (unchanged) -->
            <div class="search-bar">
                <form method="GET" action="{{ route('admin.users') }}" style="display:flex; gap:0.75rem; flex:1;">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or institutional email...">
                    <button type="submit" class="btn-search">Search</button>
                    @if($search)
                        <a href="{{ route('admin.users') }}" class="btn-clear">Clear</a>
                    @endif
                </form>
            </div>

            <!-- New: View All Users Button -->
            <div style="margin-bottom: 1.5rem;">
                <button @click="openUsersPopup" class="btn btn-primary"> View All Users</button>
            </div>

            <div class="user-grid">
                <!-- User List (Left) -->
                <div class="user-list-panel">
                    <h3>Users</h3>
                    @if($search && $users->count() > 0)
                        @foreach($users as $user)
                            <div class="user-card {{ $selectedUser && $selectedUser->id == $user->id ? 'selected' : '' }}">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <a href="{{ route('admin.users', ['search' => $search, 'user_id' => $user->id]) }}" style="flex:1;">
                                        <p class="user-name">{{ $user->full_name }}</p>
                                        <p class="user-email">{{ $user->institutional_email }}</p>
                                        <div class="user-meta">
                                            <span class="badge-role {{ $user->role == 'mis' ? 'mis' : 'professor' }}">{{ ucfirst($user->role) }}</span>
                                            <span class="badge-role {{ $user->is_active ? 'badge-approved' : 'badge-declined' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                        </div>
                                    </a>
                                    <form action="{{ route('admin.users.toggle-active', $user->id) }}" method="POST" style="margin-left:0.5rem;">
                                        @csrf
                                        <button type="submit" onclick="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this account?')"
                                            class="toggle-btn {{ $user->is_active ? 'deactivate' : 'activate' }}">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @elseif($search && $users->count() == 0)
                        <p style="color:#64748b;">No users found.</p>
                    @else
                        <p style="color:#64748b;">Enter a search term to find users, or click "View All Users" to list all.</p>
                    @endif
                </div>

                <!-- Reservation History (Right) -->
                <div class="history-panel">
                    <h3>
                        @if($selectedUser)
                            Reservation History: {{ $selectedUser->full_name }}
                        @else
                            Select a user to view history
                        @endif
                    </h3>

                    @if($selectedUser)
                        @if($reservations->count() > 0)
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Room</th><th>Date</th><th>Time Slot</th><th>Activity</th><th>Pax</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservations as $res)
                                    <tr>
                                        <td>{{ $res->room->name }}</td>
                                        <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                                        <td>{{ $res->time_slot }}</td>
                                        <td>{{ $res->activity_name }}</td>
                                        <td>{{ $res->pax }}</td>
                                        <td>
                                            <span class="badge
                                                @if($res->status == 'approved') badge-approved
                                                @elseif($res->status == 'pending') badge-pending
                                                @elseif($res->status == 'declined') badge-declined
                                                @elseif($res->status == 'ongoing') badge-ongoing
                                                @elseif($res->status == 'done') badge-done
                                                @elseif($res->status == 'cancelled') badge-cancelled
                                                @elseif($res->status == 'expired') badge-expired
                                                @endif">
                                                {{ ucfirst($res->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p style="color:#64748b;">No reservation history for this user.</p>
                        @endif
                    @else
                        <p style="color:#64748b;">Click on a user from the list to view their reservation history.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- POPUP: All Users -->
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

</body>
</html>