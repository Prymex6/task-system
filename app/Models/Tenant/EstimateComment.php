<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EstimateComment extends Model
{
    protected $fillable = ['estimate_id', 'user_id', 'body', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
