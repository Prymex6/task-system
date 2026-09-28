<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DepartmentHr extends Model
{
    protected $table = 'departments_hr';

    protected $fillable = ['name', 'manager_id', 'description'];
}
