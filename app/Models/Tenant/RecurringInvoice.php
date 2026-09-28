<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RecurringInvoice extends Model
{
    protected $fillable = ['client_id', 'created_by', 'title', 'frequency', 'interval', 'next_date', 'ends_at', 'is_active', 'template_data'];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
        'next_date' => 'date',
        'ends_at' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
