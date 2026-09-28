<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    protected $fillable = ['automation_id', 'success', 'message', 'context'];
}
