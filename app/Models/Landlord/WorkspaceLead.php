<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class WorkspaceLead extends Model
{
    protected $connection = 'central';

    protected $table = 'workspace_leads';

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'website',
        'industry',
        'city',
        'source',
        'notes',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];
}
