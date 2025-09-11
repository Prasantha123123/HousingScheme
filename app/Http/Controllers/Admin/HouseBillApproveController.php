<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseBillApproveController extends Controller
{
    public function approve(Request $request, int $id)
    {
        // -------- BULK --------
        if ($request->boolean('bulk')) {
            $data = $request->validate([
                'ids'           => ['required','array','min:1'],
                'ids.*'         => ['integer','exists:HouseRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $method = $data['paymentMethod'];

            $bills = HouseRental::whereIn('id', $data['ids'])->get();
            foreach ($bills as $bill) {
                if ($bill->paidAmount < $bill->billAmount) {
                    // Skip not-fully-paid rows
                    continue;
                }
                $bill->update([
                    // don't touch paidAmount here
                    'paymentMethod' => $method,
                    'status'        => 'Approved',
                ]);

                $this->approveCascade($bill);
            }

            return back()->with('success', 'Selected bills approved.');
        }

        // -------- SINGLE --------
        $data = $request->validate([
            // optional: allow editing paid amount; if omitted, keep as-is
            'paidAmount'    => ['nullable','numeric','min:0'],
            'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            'recipt'        => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        $bill = HouseRental::findOrFail($id);

        if ($request->hasFile('recipt')) {
            $bill->recipt = $request->file('recipt')->store('receipts', 'public');
        }

        if (array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null) {
            $bill->paidAmount = min((float)$data['paidAmount'], (float)$bill->billAmount);
        }

        if ($bill->paidAmount < $bill->billAmount) {
            return back()->withErrors('Cannot approve: bill is not fully paid yet.');
        }

        $bill->paymentMethod = $data['paymentMethod'];
        $bill->status        = 'Approved';
        $bill->save();

        $this->approveCascade($bill);

        return back()->with('success', 'Bill approved.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['reason' => ['required','string','max:1000']]);

        $bill = HouseRental::findOrFail($id);
        $bill->status = 'Rejected';
        $bill->save();

        return back()->with('success', 'Bill rejected.');
    }

    protected function approveCascade(HouseRental $bill): void
    {
        // Approve all earlier fully paid rows for the same house
        HouseRental::where('houseNo', $bill->houseNo)
            ->where('month', '<=', $bill->month)
            ->where('status', '!=', 'Approved')
            ->whereColumn('paidAmount', '>=', 'billAmount')
            ->update([
                'status'        => 'Approved',
                'paymentMethod' => $bill->paymentMethod,
            ]);
    }
}
