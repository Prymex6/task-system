<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['name', 'label', 'subject', 'body_html', 'variables'];

    protected $casts = ['variables' => 'array'];
}
