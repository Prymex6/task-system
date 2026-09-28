<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    protected $fillable = ['client_id', 'project_id', 'created_by', 'number', 'status', 'currency', 'subtotal', 'tax_amount', 'total', 'issue_date', 'valid_until', 'notes'];

    protected $casts = ['issue_date' => 'date', 'valid_until' => 'date', 'accepted_at' => 'datetime', 'rejected_at' => 'datetime', 'sent_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class)->orderBy('order');
    }

    public function comments()
    {
        return $this->hasMany(EstimateComment::class)->orderBy('created_at');
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && !in_array($this->status, ['accepted', 'converted']);
    }
}
