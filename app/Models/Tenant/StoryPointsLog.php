<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class StoryPointsLog extends Model
{
    protected $fillable = ['sprint_id', 'logged_date', 'remaining_points', 'completed_points'];
}
