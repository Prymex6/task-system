<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProposalView extends Model
{
    public $timestamps = false;

    protected $fillable = ['proposal_id', 'ip_address', 'user_agent', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];
}
