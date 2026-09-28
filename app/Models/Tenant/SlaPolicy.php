<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = ['name', 'priority', 'response_hours', 'resolution_hours'];
}
