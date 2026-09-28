<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['assigned_to', 'converted_client_id', 'company_name', 'contact_name', 'email', 'phone', 'website', 'city', 'industry', 'value', 'currency', 'status', 'source', 'notes', 'converted_at'];

    protected $casts = ['value' => 'decimal:2', 'converted_at' => 'datetime'];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }
}
