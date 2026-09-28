<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectActivityLog extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'event', 'subject_type', 'subject_id', 'description', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
