<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class KbCategory extends Model
{
    protected $fillable = ['name', 'icon', 'order', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function articles()
    {
        return $this->hasMany(KbArticle::class, 'kb_category_id');
    }
}
