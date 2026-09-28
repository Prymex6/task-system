<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['created_by', 'title', 'body', 'target', 'is_important', 'expires_at'];
}
