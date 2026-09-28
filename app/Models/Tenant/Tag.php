<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    public function tasks()
    {
        return $this->morphedByMany(Task::class, 'taggable', 'taggables');
    }

    public function clients()
    {
        return $this->morphedByMany(Client::class, 'taggable', 'taggables');
    }
}
