<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = ['webhook_id', 'event', 'response_status', 'success', 'response_body'];

    protected $casts = ['success' => 'boolean'];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
