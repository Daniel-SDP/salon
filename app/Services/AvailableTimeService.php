<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;

class AvailableTimeService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAvailableTimes(int $employeeId, string $date, int $serviceDuration) : array
    {
        $employee = Employee::findOrFail($employeeId);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $workingHour = $employee->workingHours()->where('day_of_week', $dayOfWeek)->first();
        if (!$workingHour) {
            return [];
        }

        $workStart = Carbon::parse($date . ' ' . $workingHour->start_time);
        $workEnd = Carbon::parse($date . ' ' . $workingHour->end_time);

        $appointments = Appointment::where('employee_id', $employeeId)->where('date', $date)->where('status', '!=', 'canceled')->orderBy('start_time')->get();

        $busyRanges = [];

        foreach ($appointments as $appointment) {
            $busyRanges[] = [
                Carbon::parse($date . ' ' . $appointment->start_time),
                Carbon::parse($date . ' ' . $appointment->end_time),
            ];
        }

        return $this->calculateFreeSlots($workStart, $workEnd, $busyRanges, $serviceDuration);
    }

    private function calculateFreeSlots(Carbon $workStart, Carbon $workEnd, array $busyRanges, int $duration): array {
        $freeSlots = [];
        $current = $workStart->copy();

        foreach ($busyRanges as [$busyStart, $busyEnd]) {

            if ($current->lt($busyStart)) {
                $freeSlots = array_merge(
                    $freeSlots,
                    $this->splitSlots($current, $busyStart, $duration)
                );
            }

            // move pointer after busy
            if ($current->lt($busyEnd)) {
                $current = $busyEnd->copy();
            }
        }

        // after last appointment
        if ($current->lt($workEnd)) {
            $freeSlots = array_merge(
                $freeSlots,
                $this->splitSlots($current, $workEnd, $duration)
            );
        }

        return $freeSlots;
    }

    private function splitSlots(Carbon $start, Carbon $end, int $duration) : array{
        $slots = [];

        while ( $start->copy()->addMinutes($duration)->lte($end) ) {
            $slots[] = [
                'start' => $start->format('H:i'),
                'end' => $start->copy()->addMinutes($duration)->format('H:i'),
            ];

            $start->addMinutes($duration);
        }

        return $slots;
    }
}
