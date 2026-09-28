<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    // Distinct from the tenant table of the same purpose: the two live in
    // different databases, and sharing a name makes any tool that touches
    // both sets at once collide.
    protected $table = 'platform_ticket_messages';

    protected $fillable = ['ticket_id', 'author_type', 'author_name', 'message'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
}
