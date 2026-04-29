<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\AccountRequest;
use App\Models\SystemUser;
use App\Models\Room;
use App\Models\Reservation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// ==================== HELPER FUNCTIONS FOR TIME SLOT PARSING ====================



// ==================== PUBLIC ROUTES ====================

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'institutional_email' => 'required|email',
    ]);

    $user = SystemUser::where('institutional_email', $request->institutional_email)->first();

    if (!$user) {
        return redirect('/')->with('error', 'No account found with that email.');
    }

    if (!$user->is_active) {
        return redirect('/')->with('error', 'Your account has been deactivated. Please contact MIS.');
    }

    if (Hash::check($request->institutional_email, $user->password)) {
        Auth::login($user);
        $request->session()->regenerate();
        return redirect($user->isMis() ? route('admin.dashboard') : route('professor.dashboard'));
    }

    return redirect('/')->with('error', 'Invalid login. Use your institutional email as password.');
})->name('login.submit');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/request', function () {
    return view('auth.request');
})->name('request.form');

Route::post('/request', function (Request $request) {
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'personal_email' => 'required|email|max:255',
        'institutional_email' => [
            'required',
            'email',
            'max:255',
            'unique:account_requests',
            'regex:/^[a-zA-Z0-9._%+-]+@paterostechnologicalcollege\.edu\.ph$/'
        ],
    ], [
        'institutional_email.regex' => 'The institutional email must be a valid @paterostechnologicalcollege.edu.ph address.',
    ]);

    AccountRequest::create([
        'full_name' => $validated['full_name'],
        'personal_email' => $validated['personal_email'],
        'institutional_email' => $validated['institutional_email'],
        'role' => 'professor',
        'status' => 'pending',
    ]);

    return redirect('/')->with('success', 'Your account request has been submitted! Please wait for admin approval.');
})->name('request.submit');

Route::get('/status', function () {
    return view('auth.status');
})->name('request.status');

Route::post('/status', function (Request $request) {
    $request->validate([
        'personal_email' => 'required|email',
    ]);

    $accountRequest = AccountRequest::where('personal_email', $request->personal_email)->first();

    if (!$accountRequest) {
        return redirect('/')->with('not_found', 'No request found with that personal email.');
    }

    $result = [
        'full_name' => $accountRequest->full_name,
        'institutional_email' => $accountRequest->institutional_email,
        'status' => $accountRequest->status,
    ];

    return redirect('/')->with('status_result', $result);
})->name('request.status.check');

// ==================== PROTECTED ROUTES ====================

Route::middleware(['auth'])->group(function () {

    // Professor Dashboard
    Route::get('/professor', function () {
        if (!Auth::user()->isProfessor()) {
            abort(403);
        }
        return view('professor.dashboard');
    })->name('professor.dashboard');

    // Admin Dashboard
    Route::get('/admin', function () {
        if (!Auth::user()->isMis()) {
            abort(403);
        }
        return view('admin.dashboard');
    })->name('admin.dashboard');

    // Admin: Account Requests
    Route::get('/admin/account-requests', function () {
        if (!Auth::user()->isMis()) {
            abort(403);
        }
        $requests = AccountRequest::where('status', 'pending')->get();
        return view('admin.account_requests', compact('requests'));
    })->name('admin.account_requests');

    // ------------------- APPROVE -------------------
    Route::post('/admin/approve/{id}', function ($id) {
        if (!Auth::user()->isMis()) abort(403);

        $accountRequest = AccountRequest::findOrFail($id);
        $existingUser = SystemUser::where('institutional_email', $accountRequest->institutional_email)->first();
        if ($existingUser) {
            return back()->with('error', 'A user with this institutional email already exists.');
        }

        SystemUser::create([
            'full_name'           => $accountRequest->full_name,
            'personal_email'      => $accountRequest->personal_email,
            'institutional_email' => $accountRequest->institutional_email,
            'password'            => Hash::make($accountRequest->institutional_email),
            'role'                => $accountRequest->role,
        ]);

        $accountRequest->status = 'approved';
        $accountRequest->save();

        // ★ log activity
        activity_log('approved', 'AccountRequest', $accountRequest->id, "Approved account request for {$accountRequest->full_name}");

        // Send automatic email to INSTITUTIONAL email
        try {
            \Illuminate\Support\Facades\Mail::to($accountRequest->institutional_email)
                ->send(new \App\Mail\AccountStatusMail([
                    'full_name'           => $accountRequest->full_name,
                    'personal_email'      => $accountRequest->personal_email,
                    'institutional_email' => $accountRequest->institutional_email,
                    'status'              => 'approved',
                ]));
        } catch (\Exception $e) {
            \Log::error('Approval email failed: ' . $e->getMessage());
        }

        return back()->with('success', "Account approved & email sent to {$accountRequest->institutional_email}.");
    })->name('admin.approve');

    // ------------------- DECLINE -------------------
    Route::post('/admin/decline/{id}', function ($id) {
        if (!Auth::user()->isMis()) abort(403);

        $accountRequest = AccountRequest::findOrFail($id);
        $accountRequest->status = 'rejected';
        $accountRequest->save();

        // ★ log activity
        activity_log('declined', 'AccountRequest', $accountRequest->id, "Declined account request for {$accountRequest->full_name}");

        // Send automatic email to INSTITUTIONAL email
        try {
            \Illuminate\Support\Facades\Mail::to($accountRequest->institutional_email)
                ->send(new \App\Mail\AccountStatusMail([
                    'full_name'           => $accountRequest->full_name,
                    'personal_email'      => $accountRequest->personal_email,
                    'institutional_email' => $accountRequest->institutional_email,
                    'status'              => 'declined',
                ]));
        } catch (\Exception $e) {
            \Log::error('Decline email failed: ' . $e->getMessage());
        }

        return back()->with('success', "Account declined & email sent to {$accountRequest->institutional_email}.");
    })->name('admin.decline');

    // Admin: Reservations Pending
    Route::get('/admin/reservations/pending', function () {
        if (!Auth::user()->isMis()) abort(403);
        $reservations = Reservation::where('status', 'pending')
            ->where('hold_expires_at', '>', now())
            ->with('user', 'room')
            ->latest()
            ->get();
        return view('admin.reservations_pending', compact('reservations'));
    })->name('admin.reservations.pending');

    Route::post('/admin/reservations/{id}/approve', function ($id) {
        if (!Auth::user()->isMis()) abort(403);
        $reservation = Reservation::with('user', 'room')->findOrFail($id);
        if ($reservation->status !== 'pending') return back()->with('error', 'Reservation is not pending.');
        if ($reservation->hold_expires_at && $reservation->hold_expires_at < now()) {
            $reservation->status = 'expired';
            $reservation->save();
            return back()->with('error', 'Reservation hold expired.');
        }
        $reservation->status = 'approved';
        $reservation->hold_expires_at = null;
        $reservation->save();

        // ★ log activity
        activity_log('approved', 'Reservation', $reservation->id, "Reservation approved: {$reservation->activity_name} by {$reservation->user->full_name} in {$reservation->room->name}");

        // Flash for old email button (can be removed later)
        session()->flash('last_reservation_action', 'approved');
        session()->flash('reservation_data', [
            'id' => $reservation->id,
            'professor_name' => $reservation->user->full_name,
            'professor_email' => $reservation->user->institutional_email,
            'room' => $reservation->room->name,
            'date' => $reservation->reservation_date->format('F d, Y'),
            'time_slot' => $reservation->time_slot,
            'activity' => $reservation->activity_name,
            'pax' => $reservation->pax,
        ]);

        // Send automatic email to institutional email
        try {
            \Illuminate\Support\Facades\Mail::to($reservation->user->institutional_email)
                ->send(new \App\Mail\ReservationStatusMail([
                    'professor_name'     => $reservation->user->full_name,
                    'institutional_email'=> $reservation->user->institutional_email,
                    'room'               => $reservation->room->name,
                    'date'               => $reservation->reservation_date->format('F d, Y'),
                    'time_slot'          => $reservation->time_slot,
                    'activity'           => $reservation->activity_name,
                    'pax'                => $reservation->pax,
                    'status'             => 'approved',
                ]));
        } catch (\Exception $e) {
            \Log::error('Reservation approval email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Reservation approved & email sent to ' . $reservation->user->institutional_email . '.');
    })->name('admin.reservations.approve');

    Route::post('/admin/reservations/{id}/decline', function (Request $request, $id) {
        if (!Auth::user()->isMis()) abort(403);
        $reservation = Reservation::with('user', 'room')->findOrFail($id);
        if ($reservation->status !== 'pending') return back()->with('error', 'Reservation is not pending.');

        $request->validate([
            'reason_type' => 'required|in:schedule_conflict,duplicate,invalid,other',
            'reason_notes' => 'nullable|string',
        ]);

        $reasonLabels = [
            'schedule_conflict' => 'Schedule Conflict',
            'duplicate' => 'Duplicate Booking',
            'invalid' => 'Invalid Request',
            'other' => 'Other',
        ];
        $reasonText = $reasonLabels[$request->reason_type];
        if ($request->reason_type === 'other' && $request->reason_notes) {
            $reasonText .= ': ' . $request->reason_notes;
        }

        $reservation->status = 'declined';
        $reservation->admin_remarks = $reasonText;
        $reservation->hold_expires_at = null;
        $reservation->save();

        // ★ log activity
        activity_log('declined', 'Reservation', $reservation->id, "Reservation declined: {$reservation->activity_name} by {$reservation->user->full_name} in {$reservation->room->name}");

        // Send automatic email to institutional email
        try {
            \Illuminate\Support\Facades\Mail::to($reservation->user->institutional_email)
                ->send(new \App\Mail\ReservationStatusMail([
                    'professor_name'     => $reservation->user->full_name,
                    'institutional_email'=> $reservation->user->institutional_email,
                    'room'               => $reservation->room->name,
                    'date'               => $reservation->reservation_date->format('F d, Y'),
                    'time_slot'          => $reservation->time_slot,
                    'activity'           => $reservation->activity_name,
                    'pax'                => $reservation->pax,
                    'status'             => 'declined',
                    'reason'             => $reasonText,
                ]));
        } catch (\Exception $e) {
            \Log::error('Decline email failed: ' . $e->getMessage());
        }

        return back()->with('success', "Reservation declined & email sent to {$reservation->user->institutional_email}.");
    })->name('admin.reservations.decline');

    // Admin: Active Reservations (approved & ongoing)
    Route::get('/admin/reservations/active', function () {
        if (!Auth::user()->isMis()) abort(403);
        $reservations = Reservation::whereIn('status', ['approved', 'ongoing'])
            ->with('user', 'room')
            ->orderBy('reservation_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get();
        return view('admin.reservations_active', compact('reservations'));
    })->name('admin.reservations.active');

    // Admin: Archived Reservations (declined, expired, done, cancelled)
    Route::get('/admin/reservations/archived', function () {
        if (!Auth::user()->isMis()) abort(403);
        $reservations = Reservation::whereIn('status', ['declined', 'expired', 'done', 'cancelled'])
            ->with('user', 'room')
            ->orderBy('reservation_date', 'desc')
            ->get();
        return view('admin.reservations_archived', compact('reservations'));
    })->name('admin.reservations.archived');

    // Professor cancels own pending reservation
    Route::post('/professor/reservations/{id}/cancel', function ($id) {
        $reservation = Reservation::findOrFail($id);
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }
        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Only pending reservations can be cancelled.');
        }
        $reservation->status = 'cancelled';
        $reservation->hold_expires_at = null;
        $reservation->save();
        return back()->with('success', 'Reservation cancelled.');
    })->name('professor.reservations.cancel');

    // Admin cancels an approved/ongoing reservation (free up slot)
    Route::post('/admin/reservations/{id}/cancel', function (Request $request, $id) {
        if (!Auth::user()->isMis()) abort(403);
        $reservation = Reservation::with('user', 'room')->findOrFail($id);
        if (!in_array($reservation->status, ['approved', 'ongoing'])) {
            return back()->with('error', 'Only approved/ongoing reservations can be cancelled.');
        }

        $request->validate([
            'reason_type' => 'required|in:schedule_conflict,unavailable,duplicate,invalid,other',
            'reason_notes' => 'nullable|string',
        ]);

        $reasonLabels = [
            'schedule_conflict' => 'Schedule Conflict',
            'unavailable' => 'Unavailable',
            'duplicate' => 'Duplicate',
            'invalid' => 'Invalid',
            'other' => 'Other',
        ];

        $reasonText = $reasonLabels[$request->reason_type];
        if ($request->reason_type === 'other' && $request->reason_notes) {
            $reasonText .= ': ' . $request->reason_notes;
        }

        $reservation->status = 'cancelled';
        $reservation->admin_remarks = $reasonText;
        $reservation->save();

        // ★ log activity
        activity_log('cancelled', 'Reservation', $reservation->id, "Reservation cancelled: {$reservation->activity_name} by {$reservation->user->full_name} — Reason: {$reasonText}");

        // Send automatic email to institutional email
        try {
            \Illuminate\Support\Facades\Mail::to($reservation->user->institutional_email)
                ->send(new \App\Mail\ReservationStatusMail([
                    'professor_name'     => $reservation->user->full_name,
                    'institutional_email'=> $reservation->user->institutional_email,
                    'room'               => $reservation->room->name,
                    'date'               => $reservation->reservation_date->format('F d, Y'),
                    'time_slot'          => $reservation->time_slot,
                    'activity'           => $reservation->activity_name,
                    'pax'                => $reservation->pax,
                    'status'             => 'cancelled',
                    'reason'             => $reasonText,
                ]));
        } catch (\Exception $e) {
            \Log::error('Cancel email failed: ' . $e->getMessage());
        }

        return back()->with('success', "Reservation cancelled & email sent to {$reservation->user->institutional_email}.");
    })->name('admin.reservations.cancel');

    // Admin: Manage Users (Search and View History)
    Route::get('/admin/users', function (Request $request) {
        if (!Auth::user()->isMis()) abort(403);

        $search = $request->query('search');
        $users = collect();
        $selectedUser = null;
        $reservations = collect();

        if ($search) {
            $users = SystemUser::where('institutional_email', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orderBy('full_name')
                ->limit(20)
                ->get();
        } else {
            $users = SystemUser::orderBy('full_name')->limit(50)->get();
        }

        $userId = $request->query('user_id');
        if ($userId) {
            $selectedUser = SystemUser::find($userId);
            if ($selectedUser) {
                $reservations = Reservation::where('user_id', $userId)
                    ->with('room')
                    ->orderBy('reservation_date', 'desc')
                    ->get();
            }
        }

        return view('admin.manage_users', compact('users', 'search', 'selectedUser', 'reservations'));
    })->name('admin.users');

    // Admin: Toggle user active status
    Route::post('/admin/users/{id}/toggle-active', function ($id) {
        if (!Auth::user()->isMis()) abort(403);

        $user = SystemUser::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // ★ log activity
        activity_log($user->is_active ? 'activated' : 'deactivated', 'SystemUser', $user->id, "User {$user->full_name} " . ($user->is_active ? 'activated' : 'deactivated'));

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$user->full_name} has been {$status}.");
    })->name('admin.users.toggle-active');

    // Admin: Room Management
    Route::get('/admin/rooms', function () {
        if (!Auth::user()->isMis()) abort(403);
        $rooms = Room::withCount(['reservations as active_bookings_count' => function ($query) {
            $query->whereIn('status', ['approved', 'ongoing']);
        }])->orderBy('name')->get();
        return view('admin.rooms.index', compact('rooms'));
    })->name('admin.rooms.index');

    // Admin: Show single room calendar
    Route::get('/admin/rooms/{id}', function ($id) {
        if (!Auth::user()->isMis()) abort(403);
        $room = Room::findOrFail($id);

        $reservations = Reservation::where('room_id', $id)
            ->whereIn('status', ['approved', 'ongoing', 'done'])
            ->with('user')
            ->orderBy('reservation_date')
            ->orderBy('time_slot')
            ->get()
            ->groupBy('reservation_date');

        return view('admin.rooms.show', compact('room', 'reservations'));
    })->name('admin.rooms.show');

    // Admin: Store new room
    Route::post('/admin/rooms', function (Request $request) {
        if (!Auth::user()->isMis()) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name',
            'type' => 'required|in:laboratory,lecture',
            'capacity' => 'required|integer|min:1',
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'capacity' => $validated['capacity'],
            'is_active' => true,
        ]);

        // ★ log activity
        activity_log('created', 'Room', $room->id, "Room {$validated['name']} created");

        return redirect()->route('admin.rooms.index')->with('success', 'Room added successfully.');
    })->name('admin.rooms.store');

    // Admin: Toggle room active status
    Route::post('/admin/rooms/{id}/toggle', function ($id) {
        if (!Auth::user()->isMis()) abort(403);
        $room = Room::findOrFail($id);
        $room->is_active = !$room->is_active;
        $room->save();

        // ★ log activity
        activity_log($room->is_active ? 'activated' : 'deactivated', 'Room', $room->id, "Room {$room->name} " . ($room->is_active ? 'activated' : 'deactivated'));

        $status = $room->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Room {$room->name} has been {$status}.");
    })->name('admin.rooms.toggle');

    // Admin: Block a time slot (maintenance/admin use)
    Route::post('/admin/rooms/{roomId}/block', function (Request $request, $roomId) {
    if (!Auth::user()->isMis()) abort(403);
    
    $request->validate([
        'date' => 'required|date|after_or_equal:today',
        'time_slot' => 'required|string',
        'type' => 'required|in:maintenance,admin_use',
        'notes' => 'nullable|string',
    ]);

    $room = Room::findOrFail($roomId);
    
    // Check if already blocked
    $existingBlock = \App\Models\BlockedSlot::where('room_id', $roomId)
        ->where('date', $request->date)
        ->where('time_slot', $request->time_slot)
        ->first();
    if ($existingBlock) {
        return back()->with('error', 'This time slot is already blocked.');
    }

    // Check for conflicting approved/ongoing reservations
    $conflictingReservation = Reservation::where('room_id', $roomId)
        ->where('reservation_date', $request->date)
        ->where('time_slot', $request->time_slot)
        ->whereIn('status', ['approved', 'ongoing'])
        ->with('user')
        ->first();

    $cancelledReservation = null;
    $reasonText = $request->type === 'maintenance' ? 'Cancelled due to maintenance' : 'Cancelled due to admin use';
    
    if ($conflictingReservation) {
        $conflictingReservation->status = 'cancelled';
        $conflictingReservation->admin_remarks = $reasonText;
        $conflictingReservation->save();
        $cancelledReservation = $conflictingReservation;

        // ★ Send automatic email to institutional email
        try {
            \Illuminate\Support\Facades\Mail::to($conflictingReservation->user->institutional_email)
                ->send(new \App\Mail\ReservationStatusMail([
                    'professor_name'     => $conflictingReservation->user->full_name,
                    'institutional_email'=> $conflictingReservation->user->institutional_email,
                    'room'               => $room->name,
                    'date'               => $conflictingReservation->reservation_date->format('F d, Y'),
                    'time_slot'          => $conflictingReservation->time_slot,
                    'activity'           => $conflictingReservation->activity_name,
                    'pax'                => $conflictingReservation->pax,
                    'status'             => 'cancelled',
                    'reason'             => $reasonText,
                ]));
        } catch (\Exception $e) {
            \Log::error('Maintenance cancellation email failed: ' . $e->getMessage());
        }
    }

    // Create block
    $block = \App\Models\BlockedSlot::create([
        'room_id' => $roomId,
        'date' => $request->date,
        'time_slot' => $request->time_slot,
        'type' => $request->type,
        'created_by' => Auth::id(),
        'notes' => $request->notes,
    ]);

    if ($cancelledReservation) {
        return back()->with('success', "Time slot blocked and email sent to {$conflictingReservation->user->institutional_email}. Existing reservation was cancelled.");
    }

    return back()->with('success', 'Time slot blocked successfully.');
})->name('admin.rooms.block');

    // Admin: Unblock a time slot
    Route::post('/admin/rooms/{roomId}/unblock', function (Request $request, $roomId) {
        if (!Auth::user()->isMis()) abort(403);

        $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|string',
        ]);

        $block = \App\Models\BlockedSlot::where('room_id', $roomId)
            ->where('date', $request->date)
            ->where('time_slot', $request->time_slot)
            ->firstOrFail();

        $block->delete();

        // ★ log activity
        activity_log('unblocked', 'Room', $roomId, "Unblocked slot {$request->time_slot} on {$request->date} for {$block->room->name}");

        session()->flash('unblocked_slot', [
            'room_name' => $block->room->name,
            'date' => $block->date->format('F d, Y'),
            'time_slot' => $block->time_slot,
        ]);
        session()->flash('show_available_email', true);

        return back()->with('success', 'Time slot unblocked and is now available.');
    })->name('admin.rooms.unblock');

    // Professor marks arrival
    Route::post('/professor/reservations/{id}/check-in', function ($id) {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['approved', 'ongoing'])
            ->firstOrFail();

        $now = now()->setTimezone('Asia/Manila');
        $slotStart = \Carbon\Carbon::parse($reservation->reservation_date->toDateString() . ' ' . parseSlotStartTime($reservation->time_slot), 'Asia/Manila');
        $slotEnd   = \Carbon\Carbon::parse($reservation->reservation_date->toDateString() . ' ' . parseSlotEndTime($reservation->time_slot), 'Asia/Manila');

        if ($now->lt($slotStart)) {
            return back()->with('error', 'You can only mark as arrived during the time slot.');
        }
        if ($now->gt($slotEnd)) {
            return back()->with('error', 'The time slot has already ended.');
        }
        if ($reservation->checked_in_at) {
            return back()->with('error', 'You have already marked your arrival.');
        }

        $reservation->checked_in_at = $now;
        $reservation->save();

        // ★ log activity
        activity_log('checked_in', 'Reservation', $reservation->id, "{$reservation->user->full_name} marked arrival for {$reservation->activity_name} in {$reservation->room->name}");

        return back()->with('success', 'Arrival recorded at ' . $now->format('h:i A'));
    })->name('professor.check-in');

    // API: Pending Account Requests (for dashboard popup)
    Route::get('/api/admin/pending-account-requests', function () {
        if (!Auth::user()->isMis()) abort(403);
        return \App\Models\AccountRequest::where('status', 'pending')->latest()->get();
    });

    // API: Pending Reservation Requests (for dashboard popup)
    Route::get('/api/admin/pending-reservations', function () {
        if (!Auth::user()->isMis()) abort(403);
        return \App\Models\Reservation::where('status', 'pending')
            ->where('hold_expires_at', '>', now())
            ->with('user', 'room')
            ->latest()
            ->get();
    });

    // API: Active Reservations (for dashboard popup)
    Route::get('/api/admin/active-reservations', function () {
        if (!Auth::user()->isMis()) abort(403);
        return \App\Models\Reservation::whereIn('status', ['approved', 'ongoing'])
            ->with('user', 'room')
            ->latest()
            ->get();
    });

    // API: Get all system users (for Manage Users popup)
    Route::get('/api/admin/system-users', function () {
        if (!Auth::user()->isMis()) abort(403);
        return \App\Models\SystemUser::select('id','full_name','institutional_email','role','is_active')
            ->orderBy('full_name')
            ->get();
    });

    // Check if blocking a slot will conflict with a reservation
    Route::get('/api/admin/check-slot-conflict', function (Request $request) {
        if (!Auth::user()->isMis()) abort(403);
        $request->validate([
            'room_id' => 'required|integer',
            'date' => 'required|date',
            'time_slot' => 'required|string',
        ]);

        $conflict = Reservation::where('room_id', $request->room_id)
            ->where('reservation_date', $request->date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['approved', 'ongoing'])
            ->with('user')
            ->first();

        return response()->json([
            'conflict' => $conflict ? true : false,
            'reservation' => $conflict ? [
                'professor' => $conflict->user->full_name,
                'activity' => $conflict->activity_name,
            ] : null,
        ]);
    })->name('api.admin.check-slot-conflict');

    // ==================== BOOKING API ROUTES (with time filtering) ====================

    // GET available rooms with time slots
    Route::get('/api/available-rooms', function (Request $request) {
        $date = $request->date;
        $rooms = Room::where('is_active', true)->get();

        $now = now()->setTimezone('Asia/Manila');
        $isToday = ($date === $now->toDateString());

        $availableRooms = [];
        foreach ($rooms as $room) {
            $timeSlots = $room->type === 'laboratory' 
                ? ['7-12 pm', '12-3 pm', '4-9 pm'] 
                : ['7-10 am', '10-1 pm', '1-4 pm', '4-7 pm'];

            $slotsWithStatus = [];
            foreach ($timeSlots as $slot) {
                $isBlocked = \App\Models\BlockedSlot::isBlocked($room->id, $date, $slot);
                $blockInfo = null;
                if ($isBlocked) {
                    $block = \App\Models\BlockedSlot::where('room_id', $room->id)
                        ->where('date', $date)
                        ->where('time_slot', $slot)
                        ->first();
                    $blockInfo = [
                        'type' => $block->type,
                        'label' => $block->type === 'maintenance' ? 'Maintenance' : 'Admin Use'
                    ];
                }

                $isAvailable = !$isBlocked && Reservation::isSlotAvailable($room->id, $date, $slot);

                if ($isToday && !$isBlocked) {
                    $slotEndTime = parseSlotEndTime($slot);
                    list($endHour, $endMinute) = explode(':', $slotEndTime);
                    $slotEndDateTime = \Carbon\Carbon::parse($date, 'Asia/Manila')->setTime($endHour, $endMinute, 0);

                    $slotStartTime = parseSlotStartTime($slot);
                    list($startHour, $startMinute) = explode(':', $slotStartTime);
                    $slotStartDateTime = \Carbon\Carbon::parse($date, 'Asia/Manila')->setTime($startHour, $startMinute, 0);
                    $cutoffTime = $slotStartDateTime->copy()->subHour();

                    if ($now->greaterThanOrEqualTo($slotEndDateTime) || $now->greaterThanOrEqualTo($cutoffTime)) {
                        $isAvailable = false;
                    }
                }

                $slotsWithStatus[] = [
                    'slot' => $slot,
                    'available' => $isAvailable,
                    'blocked' => $isBlocked,
                    'block_info' => $blockInfo
                ];
            }

            if (count($slotsWithStatus) > 0) {
                $roomData = $room->toArray();
                $roomData['time_slots'] = $slotsWithStatus;
                $availableRooms[] = $roomData;
            }
        }
        return response()->json($availableRooms);
    });

    // POST create reservation
    Route::post('/api/reservations', function (Request $request) {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'room_id' => 'required|exists:rooms,id',
            'time_slot' => 'required|string',
            'activity_name' => 'required|string',
            'pax' => 'required|integer|min:1',
        ]);

        $room = Room::findOrFail($request->room_id);
        $validSlots = $room->type === 'laboratory' 
            ? ['7-12 pm', '12-3 pm', '4-9 pm'] 
            : ['7-10 am', '10-1 pm', '1-4 pm', '4-7 pm'];

        if (!in_array($request->time_slot, $validSlots)) {
            return response()->json(['success' => false, 'message' => 'Invalid time slot.']);
        }

        $date = $request->date;
        $timeSlot = $request->time_slot;
        $now = now()->setTimezone('Asia/Manila');

        if ($date === $now->toDateString()) {
            $slotEndTime = parseSlotEndTime($timeSlot);
            list($endHour, $endMinute) = explode(':', $slotEndTime);
            $slotEndDateTime = \Carbon\Carbon::parse($date, 'Asia/Manila')->setTime($endHour, $endMinute);
            if ($now->greaterThanOrEqualTo($slotEndDateTime)) {
                return response()->json(['success' => false, 'message' => 'This time slot has already ended.']);
            }

            $slotStartTime = parseSlotStartTime($timeSlot);
            list($startHour, $startMinute) = explode(':', $slotStartTime);
            $slotStartDateTime = \Carbon\Carbon::parse($date, 'Asia/Manila')->setTime($startHour, $startMinute);
            if ($now->greaterThanOrEqualTo($slotStartDateTime)) {
                return response()->json(['success' => false, 'message' => 'This time slot has already started.']);
            }
        }

        if (!Reservation::isSlotAvailable($room->id, $date, $timeSlot)) {
            return response()->json(['success' => false, 'message' => 'Slot no longer available.']);
        }

        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'room_id' => $room->id,
            'reservation_date' => $date,
            'time_slot' => $timeSlot,
            'activity_name' => $request->activity_name,
            'pax' => $request->pax,
            'status' => 'pending',
            'hold_expires_at' => now()->addMinutes(15),
        ]);

        return response()->json(['success' => true]);
    });

});

// TEMPORARY – create/reset admin account (delete after first use)
Route::get('/create-admin', function () {
    $user = \App\Models\SystemUser::find(1);
    if ($user) {
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make('admin@paterostechnologicalcollege.edu.ph')
        ]);
        return 'Admin password updated. You can now log in.';
    }
    \App\Models\SystemUser::create([
        'full_name'           => 'MIS Administrator',
        'personal_email'      => 'admin@example.com',
        'institutional_email' => 'admin@paterostechnologicalcollege.edu.ph',
        'password'            => \Illuminate\Support\Facades\Hash::make('admin@paterostechnologicalcollege.edu.ph'),
        'role'                => 'mis',
        'is_active'           => true,
    ]);
    return 'Admin account created.';
});