<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ContractRenewal extends Model
{
    protected $fillable = ['contract_id', 'start_date', 'end_date', 'value', 'notes', 'created_by'];
}
