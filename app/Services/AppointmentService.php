<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use RuntimeException;

class AppointmentService
{
    /**
     * ثبت رزرو با رعایت Lock و Capacity
     */
    public function reserve(array $data): Appointment
    {
        $start = Carbon::parse($data['start_time']);
        $duration = $data['duration'] ?? 30;
        $buffer = $data['buffer'] ?? 0;

        $end = $start->copy()->addMinutes($duration + $buffer);
        $data['end_time'] = $end->format('H:i');

        $serviceId = $data['service_id'] ?? null;
        $capacity = 1;

        if ($serviceId) {
            $capacity = Service::find($serviceId)?->capacity ?? 1;
        }

        // کلید Lock برای هر اسلات
        $lockKey = $this->lockKey($data['employee_id'], $data['date'], $data['start_time']);

        return Cache::lock($lockKey, 10)->block(5, function () use ($data, $capacity, $start, $end) {

            return DB::transaction(function () use ($data, $capacity, $start, $end) {

                // تعداد رزروهای موجود در این اسلات
                $existing = Appointment::where('employee_id', $data['employee_id'])
                    ->where('date', $data['date'])
                    ->where('status', '!=', 'canceled')
                    ->where(function($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                            ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')]);
                    })
                    ->count();

                if ($existing >= $capacity) {
                    throw new RuntimeException('The selected slot is full.');
                }

                return Appointment::create($data);
            });
        });
    }

    /**
     * تولید کلید Lock برای هر اسلات
     */
    private function lockKey(int $employeeId, string $date, string $startTime): string
    {
        return "slot:{$employeeId}:{$date}:{$startTime}";
    }
}
