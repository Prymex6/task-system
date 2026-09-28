<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * A line on the CRM timeline of a client, lead or deal.
 */
class Activity extends Model
{
    protected $fillable = ['subject_type', 'subject_id', 'user_id', 'action', 'data'];

    protected $casts = ['data' => 'array'];

    public function subject()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
