<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectDiscussion extends Model
{
    protected $fillable = ['project_id', 'created_by', 'title', 'body', 'is_pinned', 'is_closed'];

    protected $casts = ['is_pinned' => 'boolean', 'is_closed' => 'boolean'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(ProjectDiscussionComment::class)->orderBy('created_at');
    }
}
