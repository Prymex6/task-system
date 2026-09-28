<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'body', 'edited_at'];
}
