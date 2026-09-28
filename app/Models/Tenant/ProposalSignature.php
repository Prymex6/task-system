<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProposalSignature extends Model
{
    protected $fillable = [
        'proposal_id', 'signer_name', 'signer_email', 'ip_address', 'signed_at', 'signature_data',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
