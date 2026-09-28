<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectFileFolder extends Model
{
    protected $fillable = ['project_id', 'parent_id', 'name', 'created_by'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProjectFileFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectFileFolder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class, 'folder_id');
    }
}
