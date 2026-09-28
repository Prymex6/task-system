<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'symbol', 'name', 'rate_to_base', 'is_default', 'is_active'];

    protected $casts = [
        'rate_to_base' => 'decimal:6',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
