<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = ['client_id', 'contract_type_id', 'created_by', 'subject', 'body', 'value', 'currency', 'start_date', 'end_date', 'status', 'signed_at', 'sent_at'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'signed_at' => 'datetime', 'sent_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function type()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function renewals()
    {
        return $this->hasMany(ContractRenewal::class);
    }

    public function comments()
    {
        return $this->hasMany(ContractComment::class)->orderBy('created_at');
    }
}
