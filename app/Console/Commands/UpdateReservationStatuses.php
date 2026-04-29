<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use App\Mail\ReservationReminder;
use Illuminate\Support\Facades\Mail;

class UpdateReservationStatuses extends Command
{
    protected $signature = 'reservations:update-statuses';
    protected $description = 'Update reservation statuses to ongoing, done, or expired based on time.';

    public function handle()
    {
        $now = Carbon::now()->setTimezone('Asia/Manila');
        $today = $now->toDateString();
        $currentTime = $now->format('H:i');

        // 1. Expire pending reservations with passed hold
        Reservation::where('status', 'pending')
            ->where('hold_expires_at', '<', $now)
            ->update(['status' => 'expired']);

        // 2. Mark approved reservations as ongoing if current time is within their time slot (today only)
        $todayApproved = Reservation::where('status', 'approved')
            ->where('reservation_date', $today)
            ->get();

        foreach ($todayApproved as $res) {
            list($start, $end) = $this->parseSlotTimes($res->time_slot);
            if ($currentTime >= $start && $currentTime < $end) {
                $res->status = 'ongoing';
                $res->save();
            } elseif ($currentTime >= $end) {
                // If it's past the end time but still 'approved', mark as done directly
                $res->status = 'done';
                $res->save();
            }
        }

        // 3. Mark ongoing reservations as done if current time is past the end time (today only)
        $todayOngoing = Reservation::where('status', 'ongoing')
            ->where('reservation_date', $today)
            ->get();

        foreach ($todayOngoing as $res) {
            list($start, $end) = $this->parseSlotTimes($res->time_slot);
            if ($currentTime >= $end) {
                $res->status = 'done';
                $res->save();
            }
        }

        // 4. Mark any approved or ongoing reservations from past dates as done (catch-all)
        Reservation::whereIn('status', ['approved', 'ongoing'])
            ->where('reservation_date', '<', $today)
            ->update(['status' => 'done']);

        // 5. Send email reminders 1 hour before start (NEW – safe addition)
        $this->sendReminders();

        $this->info('Reservation statuses updated successfully.');
    }

    private function parseSlotTimes($slot)
    {
        $parts = explode(' ', $slot);
        $timeRange = $parts[0];
        $meridiem = $parts[1] ?? '';
        list($startHour, $endHour) = explode('-', $timeRange);

        $start = $this->toCorrectStartHour($startHour, $meridiem, $endHour);
        $end = $this->toCorrectEndHour($endHour, $meridiem);

        return [$start, $end];
    }

    private function toCorrectEndHour($hour, $meridiem) {
        $hour = (int)$hour;
        if ($meridiem === 'pm' && $hour != 12) {
            $hour += 12;
        } elseif ($meridiem === 'am' && $hour == 12) {
            $hour = 0;
        }
        return sprintf('%02d:00', $hour);
    }

    private function toCorrectStartHour($startHour, $meridiem, $endHour) {
        $startCandidate = (int)$startHour;
        if ($meridiem === 'pm' && $startHour != 12) {
            $startCandidate += 12;
        } elseif ($meridiem === 'am' && $startHour == 12) {
            $startCandidate = 0;
        }
        $endCandidate = (int)$endHour;
        if ($meridiem === 'pm' && $endHour != 12) {
            $endCandidate += 12;
        } elseif ($meridiem === 'am' && $endHour == 12) {
            $endCandidate = 0;
        }
        if ($startCandidate > $endCandidate) {
            $startCandidate -= 12;
        }
        return sprintf('%02d:00', $startCandidate);
    }

    // ---------- NEW METHOD (safe, only adds functionality) ----------
    private function sendReminders()
{
    $now = now()->setTimezone('Asia/Manila');

    $reservations = Reservation::whereIn('status', ['approved', 'ongoing'])
        ->whereNull('notified_at')
        ->where('reservation_date', $now->toDateString())
        ->with('user', 'room')
        ->get()
        ->filter(function ($res) use ($now) {
            $slotStart = parseSlotStartTime($res->time_slot);
            [$startH, $startM] = explode(':', $slotStart);
            $startTime = $now->copy()->setTime((int)$startH, (int)$startM, 0);
            $diffInMinutes = $startTime->diffInMinutes($now, false);
            return $diffInMinutes >= 59 && $diffInMinutes <= 60;
        });

    foreach ($reservations as $res) {
        if ($res->user && $res->user->institutional_email) {
            Mail::to($res->user->institutional_email)->send(new ReservationReminder($res));
            $res->notified_at = $now;
            $res->save();

            // ★ Add activity log
            activity_log('reminder_sent', 'Reservation', $res->id,
                "1‑hour reminder sent to {$res->user->full_name} for {$res->activity_name} in {$res->room->name} at {$res->time_slot}");

            $this->info("Reminder sent to {$res->user->full_name} for reservation {$res->id}");
        }
    }
    }   // <-- this closes sendReminders()
}       // <-- THIS ONE IS MISSING – add it now to close the class