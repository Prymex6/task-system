<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectPinned extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'user_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
