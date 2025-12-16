<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    protected $fillable = [
        'day_of_week', 'employee_id', 'start_time', 'end_time'
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
