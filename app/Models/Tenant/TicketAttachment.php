<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_message_id', 'name', 'path', 'mime_type', 'size'];
}
