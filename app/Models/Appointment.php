<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id', 'salon_id', 'employee_id', 'service_id', 'date', 'start_time', 'end_time', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public static function hasOverlap(int $employeeId, string $date, string $startTime, string $endTime, ?int $ignoreId = null): bool {
        return self::where('employee_id', $employeeId)
            ->where('date', $date)
            ->where('status', '!=', 'canceled')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time',   '>', $startTime);
            })
            ->exists();
    }
}
