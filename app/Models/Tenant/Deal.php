<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = ['deal_stage_id', 'client_id', 'lead_id', 'assigned_to', 'title', 'value', 'currency', 'expected_close_date', 'notes', 'closed_at', 'order'];

    protected $casts = ['value' => 'decimal:2', 'expected_close_date' => 'date', 'closed_at' => 'datetime'];

    public function stage()
    {
        return $this->belongsTo(DealStage::class, 'deal_stage_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
