<?php

use App\Models\ActivityLog;

// ========== ACTIVITY LOG HELPER ==========
if (!function_exists('activity_log')) {
    function activity_log($action, $subjectType = null, $subjectId = null, $description = null, $details = null)
    {
        return ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
            'details'      => $details,
        ]);
    }
}

// ========== TIME SLOT PARSING HELPERS ==========
if (!function_exists('to24HourSmart')) {
    function to24HourSmart($hour, $meridiem, $endHour = null) {
        $hour = (int)$hour;
        if ($endHour === null) {
            if ($meridiem === 'pm' && $hour != 12) {
                $hour += 12;
            } elseif ($meridiem === 'am' && $hour == 12) {
                $hour = 0;
            }
            return sprintf('%02d:00', $hour);
        }
        $startCandidate = $hour;
        if ($meridiem === 'pm' && $hour != 12) {
            $startCandidate += 12;
        } elseif ($meridiem === 'am' && $hour == 12) {
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
}

if (!function_exists('parseSlotStartTime')) {
    function parseSlotStartTime($slot) {
        $parts = explode(' ', $slot);
        $timeRange = $parts[0];
        $meridiem = $parts[1] ?? '';
        list($startHour, $endHour) = explode('-', $timeRange);
        return to24HourSmart($startHour, $meridiem, $endHour);
    }
}

if (!function_exists('parseSlotEndTime')) {
    function parseSlotEndTime($slot) {
        $parts = explode(' ', $slot);
        $timeRange = $parts[0];
        $meridiem = $parts[1] ?? '';
        list($startHour, $endHour) = explode('-', $timeRange);
        return to24HourSmart($endHour, $meridiem);
    }
}