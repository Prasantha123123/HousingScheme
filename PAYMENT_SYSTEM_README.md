# Payment System Architecture

## Overview

This system implements dedicated payment tracking using `HousePayment` and `ShopPayment` tables while preserving existing controller flows and maintaining backward compatibility.

## Models

### Payment Models
- **HousePayment**: Records individual house payment transactions
- **ShopPayment**: Records individual shop payment transactions

Both models include:
- `paymentmake`: Payment amount (decimal)
- `method`: Payment method (cash|card|online)
- `recipt`: Receipt file path (note: typo preserved for DB compatibility)
- `paymenttype`: fullpayment|partpayment
- `status`: pending|inprogress|approval
- `customerPaidAt`: When customer made payment
- `approvedAt`: When admin approved payment

### Rental Models
- **HouseRental**: Enhanced with `payments()` relationship and `getBalanceAttribute()`
- **ShopRental**: Enhanced with `payments()` relationship and `getBalanceAttribute()`

## Payment Flow States

### Rental Row Statuses
1. **Pending**: No payment recorded
2. **InProgress**: Customer paid, awaiting admin approval
3. **PartPayment**: Partially paid and approved
4. **Approved**: Fully paid and approved

### Payment Row Statuses
1. **pending**: Created, awaiting admin approval
2. **inprogress**: (Reserved for gateway callbacks if needed)
3. **approval**: Admin approved

## Controller Flows

### Admin-Initiated Payments (Auto-Approve)
**Controllers**: `HouseBillApproveController`, `ShopRentalApproveController`

**Flow**:
1. Admin creates payment via `UnifiedPaymentService::make()` with `isCustomerFlow = false`
2. Payment automatically allocated to rental rows (oldest-first)
3. Rental status set to `Approved` if fully paid
4. Payment immediately approved via `UnifiedPaymentService::approve()`

### Customer-Initiated Payments (Requires Approval)
**Controllers**: `House\BillPayController`, `Shop\RentalPayController`

**Flow**:
1. Customer creates payment via `UnifiedPaymentService::make()` with `isCustomerFlow = true`
2. Payment allocated to rental rows (oldest-first)
3. Rental status set to `InProgress` (awaiting admin approval)
4. Payment remains in `pending` status
5. Admin later approves via `UnifiedPaymentService::approve()`

## Key Features

### Concurrency Safety
- All rental updates use `lockForUpdate()` in chronological order
- Payment allocation is atomic within database transactions
- Overpayment prevention through validation

### Backward Compatibility
- Original controller method signatures preserved
- Existing route paths unchanged
- DB column names unchanged (including `recipt` typo)
- Receipt accessor/mutator provides clean API: `$payment->receipt`

### Reporting Integration
- `monthly_collection_amount` and `collection_month` still updated on rental rows
- Existing UnifiedBillingService metrics continue to work
- Future reports can aggregate from payment tables directly

## Usage Examples

### Creating a Payment
```php
// Customer payment (requires approval)
$payment = $paymentService->make('house', $rentalId, [
    'amount' => 5000.00,
    'method' => 'online',
    'receipt' => 'path/to/receipt.pdf',
    'isCustomerFlow' => true
]);

// Admin payment (auto-approve)
$payment = $paymentService->make('house', $rentalId, [
    'amount' => 5000.00,
    'method' => 'cash',
    'isCustomerFlow' => false
]);
```

### Approving a Payment
```php
$approvedPayment = $paymentService->approve('house', $paymentId);
```

### Finding Pending Payment
```php
$pendingPayment = $paymentService->latestPendingPaymentForRental('house', $rentalId);
```

## Constraints

- Payment amounts validated against outstanding balance to prevent overpayment
- Only latest outstanding rental can receive new customer payments
- All monetary calculations use bcmath for precision
- Timezone: Asia/Colombo, Currency: LKR
- Bulk operations are idempotent and transaction-safe