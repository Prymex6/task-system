<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\TaskStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'order', 'is_default', 'is_closed'];

    protected $casts = ['is_default' => 'boolean', 'is_closed' => 'boolean'];

    protected static function newFactory(): TaskStatusFactory
    {
        return TaskStatusFactory::new();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
