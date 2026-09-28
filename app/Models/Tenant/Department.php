<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'email', 'manager_id'];
}
