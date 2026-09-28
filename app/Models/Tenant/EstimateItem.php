<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $fillable = ['estimate_id', 'description', 'quantity', 'unit', 'unit_price', 'tax_rate', 'discount_percent', 'total', 'order'];
}
