<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = ['user_id', 'reviewed_by', 'period', 'rating', 'strengths', 'improvements', 'goals', 'comments'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
