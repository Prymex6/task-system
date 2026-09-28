<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'client_contact_id', 'assigned_to',
        'department_id', 'sla_policy_id', 'subject', 'status', 'priority',
        'first_response_at', 'resolved_at', 'opened_at', 'closed_at',
        'sla_response_due', 'sla_resolution_due',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_response_due' => 'datetime',
        'sla_resolution_due' => 'datetime',
    ];

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contact()
    {
        return $this->belongsTo(ClientContact::class, 'client_contact_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function attachments()
    {
        return $this->hasManyThrough(TicketAttachment::class, TicketMessage::class, 'ticket_id', 'ticket_message_id');
    }

    public function rating()
    {
        return $this->hasOne(TicketRating::class);
    }
}
