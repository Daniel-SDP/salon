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
}
