<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentService
{
    public function reserve(array $data): Appointment
    {
        $start = Carbon::parse($data['start_time']);
        $duration = $data['duration'] ?? 30; // اگر داده نشده، مقدار پیش فرض
        $buffer = $data['buffer'] ?? 0;

        $end = $start->copy()->addMinutes($duration + $buffer);

        $data['end_time'] = $end->format('H:i'); // حالا وجود دارد

        $lockKey = $this->lockKey(
            $data['employee_id'],
            $data['date'],
            $data['start_time']
        );

        return Cache::lock($lockKey, 10)->block(5, function () use ($data) {

            return DB::transaction(function () use ($data) {

                // چک نهایی (حتی اگر تایم قبلاً آزاد نشان داده شده)
                $exists = Appointment::where('employee_id', $data['employee_id'])
                    ->where('date', $data['date'])
                    ->where('status', '!=', 'canceled')
                    ->where(function ($q) use ($data) {
                        $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                            ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
                    })
                    ->exists();

                if ($exists) {
                    throw new \RuntimeException('This slot previously has been reserved.');
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
