<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class HouseBillApproveController extends Controller
{
    public function approve(Request $request, int $id)
    {
        // ---------- BULK ----------
        if ($request->boolean('bulk')) {
            $data = $request->validate([
                'ids'           => ['required','array','min:1'],
                'ids.*'         => ['integer','exists:HouseRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $method = $data['paymentMethod'];

            DB::transaction(function () use ($data, $method) {
                $bills = HouseRental::whereIn('id', $data['ids'])->lockForUpdate()->get();

                foreach ($bills as $bill) {
                    // If CASH, mark this month fully paid
                    if ($method === 'cash' && (float)$bill->paidAmount < (float)$bill->billAmount) {
                        $bill->paidAmount = (float)$bill->billAmount;
                    }

                    $bill->paymentMethod = $method;
                    $bill->status        = 'Approved';
                    $bill->save();

                    // Keep your existing conditional cascade rule
                    $this->conditionallyApproveEarlier($bill);
                }
            });

            return back()->with('success', 'Selected bills approved.');
        }

        // ---------- SINGLE ----------
        $data = $request->validate([
            'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            'recipt'        => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            $bill = HouseRental::lockForUpdate()->findOrFail($id);

            if ($request->hasFile('recipt')) {
                $path = $request->file('recipt')->store('receipts', 'public');
                $bill->recipt = $path;
            }

            // If CASH, mark this month fully paid
            if ($data['paymentMethod'] === 'cash' && (float)$bill->paidAmount < (float)$bill->billAmount) {
                $bill->paidAmount = (float)$bill->billAmount;
            }

            $bill->paymentMethod = $data['paymentMethod'];
            $bill->status        = 'Approved';
            $bill->save();

            // Keep your existing conditional cascade rule
            $this->conditionallyApproveEarlier($bill);
        });

        return back()->with('success', 'Bill approved.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);

        $bill = HouseRental::findOrFail($id);
        $bill->status = 'Rejected';
        $bill->save();

        return back()->with('success', 'Bill rejected.');
    }

    /**
     * Approve earlier months only when this approved bill is the latest month
     * and its paidAmount covered carry + this month (unchanged from earlier).
     */
    protected function conditionallyApproveEarlier(HouseRental $approvedBill): void
    {
        $hasLaterOpen = HouseRental::where('houseNo', $approvedBill->houseNo)
            ->where('month', '>', $approvedBill->month)
            ->where('status', '!=', 'Approved')
            ->exists();

        if ($hasLaterOpen) return;

        $carry = (float) HouseRental::where('houseNo', $approvedBill->houseNo)
            ->where('month', '<', $approvedBill->month)
            ->get()
            ->sum(fn ($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $required = $carry + (float) $approvedBill->billAmount;
        if ((float)$approvedBill->paidAmount + 0.01 >= $required) {
            HouseRental::where('houseNo', $approvedBill->houseNo)
                ->where('month', '<', $approvedBill->month)
                ->where('status', '!=', 'Approved')
                ->update(['status' => 'Approved']);
        }
    }
}
