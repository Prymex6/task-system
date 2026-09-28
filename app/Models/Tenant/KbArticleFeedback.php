<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class KbArticleFeedback extends Model
{
    protected $fillable = [
        'kb_article_id', 'user_id', 'is_helpful', 'comment', 'ip_address',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    public function article()
    {
        return $this->belongsTo(KbArticle::class, 'kb_article_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
