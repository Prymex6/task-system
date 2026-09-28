<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ContractComment extends Model
{
    protected $fillable = ['contract_id', 'user_id', 'body', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
