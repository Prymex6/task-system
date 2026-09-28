<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = ['user_id', 'message', 'remind_at', 'is_sent', 'sent_at'];
}
