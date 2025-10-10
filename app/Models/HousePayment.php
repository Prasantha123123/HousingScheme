<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousePayment extends Model
{
    protected $table = 'housePayment';
    
    protected $fillable = [
        'houseId', 'paymentmake', 'method', 'recipt', 'paymenttype',
        'customerPaidAt', 'status', 'approvedAt'
    ];
    
    protected $casts = [
        'paymentmake' => 'decimal:2',
        'customerPaidAt' => 'datetime',
        'approvedAt' => 'datetime',
    ];

    /**
     * Accessor/Mutator alias for receipt typo
     */
    public function getReceiptAttribute()
    {
        return $this->recipt;
    }

    public function setReceiptAttribute($value)
    {
        $this->recipt = $value;
    }

    /**
     * Relationship with house rental
     */
    public function houseRental(): BelongsTo 
    {
        return $this->belongsTo(HouseRental::class, 'houseId');
    }

    /**
     * Scope for approved payments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approval');
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['pending', 'inprogress']);
    }

    /**
     * Mark payment as approved
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approval',
            'approvedAt' => now()
        ]);
        
        // Refresh parent rental totals
        $this->houseRental->refreshTotalsAndStatus();
    }

    /**
     * Mark payment as rejected
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'pending',
            'approvedAt' => null
        ]);
        
        // Refresh parent rental totals
        $this->houseRental->refreshTotalsAndStatus();
    }

    /**
     * Get formatted payment amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rs ' . number_format((float)$this->paymentmake, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approval' => 'green',
            'inprogress' => 'yellow',
            'pending' => 'gray',
            default => 'gray'
        };
    }
}