<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['name', 'color', 'requires_approval', 'days_per_year'];
}
