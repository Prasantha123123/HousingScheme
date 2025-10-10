<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseRental extends Model
{
    protected $table = 'HouseRental';
    public $timestamps = false;

    protected $fillable = [
        'houseNo','readingUnit','month','openingReading','billAmount',
        'paidAmount','monthly_collection_amount','collection_month','original_payment_amount',
        'paymentMethod','recipt','status','customer_paid_at','approved_at','timestamp'
    ];

    protected $casts = [
        'customer_paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'timestamp' => 'datetime',
        'billAmount' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'monthly_collection_amount' => 'decimal:2',
        'original_payment_amount' => 'decimal:2',
        'openingReading' => 'integer',
        'readingUnit' => 'integer',
    ];

    /**
     * Relationship with payment records
     */
    public function payments(): HasMany 
    {
        return $this->hasMany(HousePayment::class, 'houseId');
    }

    /**
     * Get approved payments only
     */
    public function approvedPayments(): HasMany
    {
        return $this->payments()->where('status', 'approval');
    }

    /**
     * Get pending payments
     */
    public function pendingPayments(): HasMany
    {
        return $this->payments()->whereIn('status', ['pending', 'inprogress']);
    }

    /**
     * Refresh paid amount and status based on approved payments
     */
    public function refreshTotalsAndStatus(): void
    {
        // Sum all approved payments
        $approvedSum = $this->payments()
            ->where('status', 'approval')
            ->sum('paymentmake');
            
        // Sum approved + in-progress payments
        $totalSum = $this->payments()
            ->whereIn('status', ['inprogress', 'approval'])
            ->sum('paymentmake');
            
        $this->paidAmount = $approvedSum;

        // Update status based on payment totals
        if ($totalSum <= 0) {
            $this->status = 'Pending';
            $this->approved_at = null;
        } elseif ($approvedSum < $this->billAmount) {
            $this->status = ($totalSum > $approvedSum) ? 'InProgress' : 'PartPayment';
            $this->approved_at = null;
        } else {
            $this->status = 'Approved';
            $this->approved_at ??= now();
        }
        
        $this->save();
    }

    /**
     * Get outstanding amount (billAmount - paidAmount)
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float)$this->billAmount - (float)$this->paidAmount);
    }

    /**
     * Get balance attribute (alias for outstanding amount)
     */
    public function getBalanceAttribute(): string
    {
        return bcsub((string)$this->billAmount, (string)$this->paidAmount, 2);
    }

    /**
     * Check if rental is fully paid
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return (float)$this->paidAmount >= (float)$this->billAmount;
    }

    /**
     * Get total payment amount including pending
     */
    public function getTotalPaymentAmountAttribute(): float
    {
        return (float)$this->payments()
            ->whereIn('status', ['inprogress', 'approval'])
            ->sum('paymentmake');
    }

    /**
     * Get payment history with status
     */
    public function getPaymentHistoryAttribute()
    {
        return $this->payments()
            ->orderBy('customerPaidAt', 'desc')
            ->get();
    }
}
