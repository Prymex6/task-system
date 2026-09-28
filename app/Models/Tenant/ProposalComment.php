<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProposalComment extends Model
{
    protected $fillable = ['proposal_id', 'user_id', 'body', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
