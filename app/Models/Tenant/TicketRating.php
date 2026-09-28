<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TicketRating extends Model
{
    protected $fillable = ['ticket_id', 'rating', 'comment'];
}
