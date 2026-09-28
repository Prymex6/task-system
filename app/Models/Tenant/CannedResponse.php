<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CannedResponse extends Model
{
    protected $fillable = ['department_id', 'title', 'body'];
}
