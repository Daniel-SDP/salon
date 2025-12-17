<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;

class AvailableTimeService
{
    /**
     * دریافت اسلات‌های آزاد برای یک کارمند و سرویس مشخص
     */
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

        if (!$workingHour) return [];

        // Buffer و Capacity
        $bufferMinutes = 0;
        $capacity = 1;
        if ($serviceId) {
            $service = Service::find($serviceId);
            $bufferMinutes = $service?->buffer_minutes ?? 0;
            $capacity = $service?->capacity ?? 1;
        }

        $totalDuration = $serviceDuration + $bufferMinutes;

        $workStart = Carbon::parse("$date {$workingHour->start_time}");
        $workEnd   = Carbon::parse("$date {$workingHour->end_time}");

        if ($workStart->copy()->addMinutes($totalDuration)->gt($workEnd)) return [];

        // گرفتن رزروهای موجود یک بار برای efficiency
        $appointments = Appointment::where('employee_id', $employeeId)
            ->where('date', $date)
            ->where('status', '!=', 'canceled')
            ->get();

        $slots = [];
        $current = $workStart->copy();

        while ($current->copy()->addMinutes($totalDuration)->lte($workEnd)) {
            $slotStart = $current->copy();
            $slotEnd   = $current->copy()->addMinutes($totalDuration);

            // تعداد رزروهای موجود در این اسلات
            $existingCount = $appointments->filter(function ($appointment) use ($slotStart, $slotEnd) {
                $aStart = Carbon::parse("$appointment->date {$appointment->start_time}");
                $aEnd   = Carbon::parse("$appointment->date {$appointment->end_time}");
                return $aStart->lt($slotEnd) && $aEnd->gt($slotStart);
            })->count();

            if ($existingCount < $capacity) {
                $slots[] = [
                    'start' => $slotStart->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
            }

            // تعیین گام بعدی اسلات
            $stepMinutes = $this->slotStepMinutes($serviceDuration, $totalDuration, $employee);
            $current->addMinutes($stepMinutes);
        }

        return $slots;
    }

    /**
     * محاسبه گام بعدی اسلات
     */
    private function slotStepMinutes(int $serviceDuration, int $totalDuration, Employee $employee): int
    {
        $strategy = $employee->slot_strategy ?? config('booking.slot_step', 'service');

        return $strategy === 'total'
            ? $totalDuration
            : $serviceDuration;
    }
}
