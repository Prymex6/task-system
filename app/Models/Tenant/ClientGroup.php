<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ClientGroup extends Model
{
    protected $fillable = ['name', 'color', 'description'];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_group_pivot');
    }
}
