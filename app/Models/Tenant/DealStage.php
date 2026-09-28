<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DealStage extends Model
{
    protected $fillable = ['name', 'color', 'order', 'win_probability', 'is_won', 'is_lost'];

    protected $casts = ['is_won' => 'boolean', 'is_lost' => 'boolean', 'win_probability' => 'decimal:2'];

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
