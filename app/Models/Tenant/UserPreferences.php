<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class UserPreferences extends Model
{
    protected $fillable = [
        'user_id', 'theme', 'language', 'timezone',
        'notifications_email', 'notifications_push', 'notifications_slack',
        'sidebar_collapsed', 'default_view', 'items_per_page',
    ];

    protected $casts = [
        'notifications_email' => 'boolean',
        'notifications_push' => 'boolean',
        'notifications_slack' => 'boolean',
        'sidebar_collapsed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
