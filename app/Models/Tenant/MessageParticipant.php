<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MessageParticipant extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'last_read_at', 'is_muted'];

    protected $casts = [
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
