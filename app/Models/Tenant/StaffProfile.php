<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    protected $fillable = ['user_id', 'position_id', 'phone', 'hired_at', 'hourly_rate', 'emergency_contact', 'bio'];
}
