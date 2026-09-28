<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['type', 'config', 'is_active'];

    protected $casts = ['config' => 'array', 'is_active' => 'boolean'];
}
