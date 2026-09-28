<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = ['invoice_id', 'created_by', 'number', 'amount', 'reason', 'issue_date'];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date:Y-m-d',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
