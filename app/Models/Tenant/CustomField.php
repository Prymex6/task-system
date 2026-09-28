<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = ['model', 'name', 'label', 'type', 'options', 'is_required', 'order'];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean'];
}
