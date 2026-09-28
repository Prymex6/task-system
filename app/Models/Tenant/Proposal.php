<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'lead_id', 'created_by',
        'title', 'body', 'notes',
        'total_net', 'total_tax', 'total_gross',
        'currency', 'status', 'valid_until',
        'view_token', 'sent_at', 'accepted_at', 'rejected_at',
        'rejection_reason', 'issue_date', 'number',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'issue_date' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total_net' => 'decimal:4',
        'total_tax' => 'decimal:4',
        'total_gross' => 'decimal:4',
    ];

    // ── Relacje ───────────────────────────────────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ProposalItem::class)->orderBy('order');
    }

    public function views()
    {
        return $this->hasMany(ProposalView::class);
    }

    public function signature()
    {
        return $this->hasOne(ProposalSignature::class);
    }

    public function comments()
    {
        return $this->hasMany(ProposalComment::class)->orderBy('created_at');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['sent', 'viewed']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status !== 'accepted';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function markViewed(): void
    {
        ProposalView::create([
            'proposal_id' => $this->id,
            'viewed_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        if (in_array($this->status, ['sent'])) {
            $this->update(['status' => 'viewed']);
        }
    }
}
