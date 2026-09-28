<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = ['name', 'description', 'created_by'];

    public function tasks()
    {
        return $this->hasMany(ProjectTemplateTask::class)->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
