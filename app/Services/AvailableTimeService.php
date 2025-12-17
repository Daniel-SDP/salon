<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AvailableTimeService
{
    /**
     * گرفتن اسلات‌های آزاد
     */
    public function getAvailableTimes(int $employeeId, string $date, int $serviceDuration, ?int $serviceId = null): array
    {
        $employee = Employee::findOrFail($employeeId);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $workingHour = $employee->workingHours()->where('day_of_week', $dayOfWeek)->first();
        if (!$workingHour) return [];

        $service = $serviceId ? Service::find($serviceId) : null;
        $bufferMinutes = $service?->buffer_minutes ?? 0;
        $capacity = $service?->capacity ?? 1;
        $totalDuration = $serviceDuration + $bufferMinutes;

        $workStart = Carbon::parse("$date {$workingHour->start_time}");
        $workEnd = Carbon::parse("$date {$workingHour->end_time}");

        if ($workStart->copy()->addMinutes($totalDuration)->gt($workEnd)) return [];

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
            $totalDuration,
            $employee,
            $serviceId,
            $capacity
        );
    }

    private function calculateFreeSlots(
        Carbon $workStart,
        Carbon $workEnd,
        array $busyRanges,
        int $serviceDuration,
        int $totalDuration,
        Employee $employee,
        ?int $serviceId,
        int $capacity
    ): array {
        $slots = [];
        $current = $workStart->copy();

        while ($current->copy()->addMinutes($totalDuration)->lte($workEnd)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($totalDuration);

            // تعداد رزروهای واقعی
            $existing = Appointment::where('employee_id', $employee->id)
                ->where('status', '!=', 'canceled')
                ->where('date', $slotStart->format('Y-m-d'))
                ->where(function($q) use ($slotStart, $slotEnd) {
                    $q->whereBetween('start_time', [$slotStart->format('H:i'), $slotEnd->format('H:i')])
                        ->orWhereBetween('end_time', [$slotStart->format('H:i'), $slotEnd->format('H:i')]);
                })
                ->count();

            // تعداد lock فعال
            $lockKey = "slot:{$employee->id}:{$slotStart->format('Y-m-d H:i')}";
            $locksCount = Cache::get($lockKey . ':count', 0);

            if (($existing + $locksCount) < $capacity) {
                $slots[] = [
                    'start' => $slotStart->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
            }

            // تعیین گام بعدی اسلات
            $stepMinutes = $this->slotStepMinutes($serviceDuration, $totalDuration, $employee);
            $current->addMinutes($stepMinutes);
        }

        return $slots;
    }

    private function slotStepMinutes(int $serviceDuration, int $totalDuration, Employee $employee): int
    {
        $strategy = $employee->slot_strategy ?? config('booking.slot_step', 'service');
        return $strategy === 'total' ? $totalDuration : $serviceDuration;
    }
}
