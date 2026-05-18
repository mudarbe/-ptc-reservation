<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Requests - PTC Admin</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{
    detailModal: false,
    detailItem: null,
    searchEmail: ''
}">

    <!-- Sidebar (same as before) -->
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
            <a href="{{ route('admin.account_requests') }}" class="active">Account Requests</a>
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
            <h1>Account Requests</h1>
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

            <div class="archive-filter-bar">

    <div class="archive-filter-left">

        <label>Search Institutional Email</label>

        <input type="text"
               x-model="searchEmail"
               placeholder="example@paterostechnologicalcollege.edu.ph"
               class="archive-date-filter">

    </div>

    <button x-show="searchEmail"
            @click="searchEmail = ''"
            class="archive-clear-btn">

        Clear

    </button>

</div>

            <div class="table-wrapper">
                <h3>Pending Account Requests</h3>
                @if($requests->count() > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                
                                <th>Institutional Email</th>
                                <th>Requested At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)

<tr id="request-{{ $request->id }}"

    x-show="
        !searchEmail ||
        '{{ strtolower($request->institutional_email) }}'
            .includes(searchEmail.toLowerCase())
    "
    class="clickable-row {{ request('highlight') == $request->id ? 'highlight-row' : '' }}"
                                @click="detailItem = {{ json_encode([
                                    'name' => $request->full_name,
                                    
                                    'institutional' => $request->institutional_email,
                                    'date' => $request->created_at->format('M d, Y H:i'),
                                    'status' => ucfirst($request->status),
                                ]) }}; detailModal = true">
                                <td>{{ $request->full_name }}</td>
                                
                                <td>{{ $request->institutional_email }}</td>
                                <td>{{ $request->created_at->format('M d, Y H:i') }}</td>
                                <td @click.stop="">
                                    <div style="display:flex; gap:0.5rem;">
                                        <form action="{{ route('admin.approve', $request->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-sm btn-approve">Approve</button>
                                        </form>
                                        <form action="{{ route('admin.decline', $request->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-sm btn-decline">Decline</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="padding:1.25rem 1.5rem; color:#64748b;">No pending account requests.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="detailModal" class="modal-overlay" @click.self="detailModal = false">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Account Request Details</h3>
                <button @click="detailModal = false" class="modal-close">&times;</button>
            </div>
            <template x-if="detailItem">

    <div class="account-detail-modern">

        <!-- TOP PROFILE -->

        <div class="account-detail-top">

            <div class="account-avatar">

                <span x-text="detailItem.name.charAt(0).toUpperCase()"></span>

            </div>

            <div>

                <h2 x-text="detailItem.name"></h2>

                <p>
                    Professor Account Request
                </p>

            </div>

        </div>

        <!-- DETAILS GRID -->

        <div class="account-detail-grid">

            <div class="account-detail-item">

                <span>Institutional Email</span>

                <strong x-text="detailItem.institutional"></strong>

            </div>

            <div class="account-detail-item">

                <span>Requested At</span>

                <strong x-text="detailItem.date"></strong>

            </div>

            <div class="account-detail-item">

                <span>Status</span>

                <div class="account-status-pill">

                    <span x-text="detailItem.status"></span>

                </div>

            </div>

        </div>

    </div>

</template>
            <div class="modal-actions" style="margin-top: 1.5rem;">
                <button @click="detailModal = false" class="btn btn-outline">Close</button>
            </div>
        </div>
    </div>

    <script>

window.addEventListener('load', () => {

    const highlighted =
        document.querySelector('.highlight-row');

    if(highlighted){

        highlighted.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    }

});

</script>

</body>
</html>