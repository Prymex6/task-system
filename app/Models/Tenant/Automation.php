<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = ['name', 'trigger', 'conditions', 'actions', 'is_active', 'run_count', 'last_run_at', 'created_by'];
}
