<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectDiscussionComment extends Model
{
    protected $fillable = ['project_discussion_id', 'user_id', 'client_contact_id', 'body', 'edited_at'];

    protected $casts = ['edited_at' => 'datetime'];

    public function discussion()
    {
        return $this->belongsTo(ProjectDiscussion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clientContact()
    {
        return $this->belongsTo(ClientContact::class);
    }
}
