<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentService
{
    /**
     * ثبت رزرو با رعایت Lock و Capacity
     */
    public function reserve(array $data): Appointment
    {
        $start = Carbon::parse($data['start_time']);
        $duration = $data['duration'] ?? 30; // مقدار پیش‌فرض
        $buffer = $data['buffer'] ?? 0;

        $end = $start->copy()->addMinutes($duration + $buffer);
        $data['end_time'] = $end->format('H:i');

        $serviceId = $data['service_id'] ?? null;
        $serviceCapacity = 1;

        if ($serviceId) {
            $serviceCapacity = Service::find($serviceId)?->capacity ?? 1;
        }

        $lockKey = $this->lockKey($data['employee_id'], $data['date'], $data['start_time']);

        return Cache::lock($lockKey, 10)->block(5, function () use ($data, $serviceCapacity, $start, $end) {

            return DB::transaction(function () use ($data, $serviceCapacity, $start, $end) {

                // تعداد رزروهای همزمان
                $existing = Appointment::where('employee_id', $data['employee_id'])
                    ->where('date', $data['date'])
                    ->where('status', '!=', 'canceled')
                    ->where('start_time', $data['start_time'])
                    ->count();

                if ($existing >= $serviceCapacity) {
                    throw new \RuntimeException('The selected slot is full.');
                }


                return Appointment::create($data);
            });
        });
    }

    private function lockKey(int $employeeId, string $date, string $startTime): string
    {
        return "slot:$employeeId:$date:$startTime";
    }
}
