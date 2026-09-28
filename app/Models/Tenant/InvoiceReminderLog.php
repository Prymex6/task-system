<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InvoiceReminderLog extends Model
{
    protected $fillable = [
        'invoice_id', 'sent_at', 'sent_to', 'channel', 'status', 'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
