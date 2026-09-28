<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    protected $fillable = [
        'name', 'company_name', 'nip', 'email', 'phone', 'website',
        'address', 'city', 'postal_code', 'country', 'currency',
        'notes', 'avatar', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(ClientContact::class)->where('is_primary', true);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function groups()
    {
        return $this->belongsToMany(ClientGroup::class, 'client_group_pivot');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
