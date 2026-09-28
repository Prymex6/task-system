<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'client_contact_id', 'body', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(ClientContact::class, 'client_contact_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }
}
