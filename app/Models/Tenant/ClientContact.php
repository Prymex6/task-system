<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\ClientContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ClientContact extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory(): ClientContactFactory
    {
        return ClientContactFactory::new();
    }

    protected $table = 'client_contacts';

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'password',
        'phone',
        'position',
        'is_primary',
        'portal_access',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'portal_access' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
