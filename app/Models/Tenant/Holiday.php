<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'country', 'is_recurring'];
}
