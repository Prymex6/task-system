<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['name', 'is_group'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
