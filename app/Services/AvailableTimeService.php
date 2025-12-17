<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;

class AvailableTimeService
{
    public function getAvailableTimes(
        int $employeeId,
        string $date,
        int $serviceDuration,
        ?int $serviceId = null
    ): array {
        $employee = Employee::findOrFail($employeeId);

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $workingHour = $employee->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHour) {
            return [];
        }

        // Buffer
        $bufferMinutes = 0;
        if ($serviceId) {
            $bufferMinutes = Service::find($serviceId)?->buffer_minutes ?? 0;
        }

        $totalDuration = $serviceDuration + $bufferMinutes;

        $workStart = Carbon::parse("$date {$workingHour->start_time}");
        $workEnd   = Carbon::parse("$date {$workingHour->end_time}");

        if ($workStart->copy()->addMinutes($totalDuration)->gt($workEnd)) {
            return [];
        }

        $appointments = Appointment::where('employee_id', $employeeId)
            ->where('date', $date)
            ->where('status', '!=', 'canceled')
            ->orderBy('start_time')
            ->get();

        $busyRanges = [];

        foreach ($appointments as $appointment) {
            $busyRanges[] = [
                Carbon::parse("$date {$appointment->start_time}"),
                Carbon::parse("$date {$appointment->end_time}"),
            ];
        }

        return $this->calculateFreeSlots(
            $workStart,
            $workEnd,
            $busyRanges,
            $serviceDuration,
            $totalDuration
        );
    }

    private function calculateFreeSlots(
        Carbon $workStart,
        Carbon $workEnd,
        array $busyRanges,
        int $serviceDuration,
        int $totalDuration
    ): array {
        $freeSlots = [];
        $current = $workStart->copy();

        foreach ($busyRanges as [$busyStart, $busyEnd]) {

            if ($current->lt($busyStart)) {
                $freeSlots = array_merge(
                    $freeSlots,
                    $this->splitSlots(
                        $current,
                        $busyStart,
                        $serviceDuration,
                        $totalDuration
                    )
                );
            }

            if ($current->lt($busyEnd)) {
                $current = $busyEnd->copy();
            }
        }

        if ($current->lt($workEnd)) {
            $freeSlots = array_merge(
                $freeSlots,
                $this->splitSlots(
                    $current,
                    $workEnd,
                    $serviceDuration,
                    $totalDuration
                )
            );
        }

        return $freeSlots;
    }

    private function splitSlots(
        Carbon $start,
        Carbon $end,
        int $serviceDuration,
        int $totalDuration
    ): array {
        $slots = [];

        $stepMinutes = $this->slotStepMinutes($serviceDuration, $totalDuration);

        while ($start->copy()->addMinutes($totalDuration)->lte($end)) {
            $slots[] = [
                'start' => $start->format('H:i'),
                'end'   => $start->copy()->addMinutes($totalDuration)->format('H:i'),
            ];

            $start->addMinutes($stepMinutes);
        }

        return $slots;
    }

    private function slotStepMinutes(int $serviceDuration, int $totalDuration): int
    {
        return config('booking.slot_step') === 'total'
            ? $totalDuration
            : $serviceDuration;
    }
}
