<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplateTask extends Model
{
    protected $fillable = ['project_template_id', 'title', 'description', 'estimated_hours', 'priority', 'order'];

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }
}
