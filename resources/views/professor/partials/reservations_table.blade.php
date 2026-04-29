@if($reservations->count() > 0)
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Date</th>
                    <th>Time Slot</th>
                    <th>Activity</th>
                    <th>Pax</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                            @else badge-done
                            @endif">
                            {{ ucfirst($res->status) }}
                        </span>
                        @if(in_array($res->status, ['cancelled', 'declined']) && $res->admin_remarks)
                            <span style="display:block; font-size:0.75rem; color:var(--prof-danger); margin-top:0.25rem;">{{ $res->admin_remarks }}</span>
                        @endif
                    </td>
                    <td>
                        @if($res->status == 'pending')
                            <form action="{{ route('professor.reservations.cancel', $res->id) }}" method="POST" onsubmit="return confirm('Cancel this reservation?');">
                                @csrf
                                <button type="submit" class="btn" style="background:var(--prof-danger); color:#fff; padding:0.3rem 0.6rem; font-size:0.8rem;">Cancel</button>
                            </form>
                        @else
                            <span style="color:var(--prof-muted);">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p style="color:var(--prof-muted);">No reservations yet.</p>
@endif