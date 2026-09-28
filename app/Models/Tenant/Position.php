<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['department_hr_id', 'name'];
}
