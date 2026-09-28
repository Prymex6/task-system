<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProposalItem extends Model
{
    protected $fillable = ['proposal_id', 'description', 'quantity', 'unit_price', 'total', 'order'];
}
