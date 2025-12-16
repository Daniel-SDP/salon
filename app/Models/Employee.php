<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name', 'salon_id', 'phone', 'is_active'
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
