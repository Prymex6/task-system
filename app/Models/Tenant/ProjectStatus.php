<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    protected $fillable = ['name', 'color', 'order', 'is_default', 'is_archived'];

    protected $casts = ['is_default' => 'boolean', 'is_archived' => 'boolean'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
