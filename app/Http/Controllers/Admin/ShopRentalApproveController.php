<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopRentalApproveController extends Controller
{
    public function approve(Request $request, int $id)
    {
        // ---------- BULK ----------
        if ($request->boolean('bulk')) {
            $data = $request->validate([
                'ids'           => ['required','array','min:1'],
                'ids.*'         => ['integer','exists:ShopRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $method = $data['paymentMethod'];

            DB::transaction(function () use ($data, $method) {
                $rows = ShopRental::whereIn('id', $data['ids'])->lockForUpdate()->get();

                foreach ($rows as $r) {
                    // If approving as CASH in bulk, mark paid in full; otherwise keep existing paidAmount
                    $paid = ($method === 'cash') ? (float) $r->billAmount : (float) $r->paidAmount;

                    $r->update([
                        'paymentMethod' => $method,
                        'paidAmount'    => $paid,
                        'status'        => 'Approved',
                    ]);
                }
            });

            return back()->with('success', 'Selected rentals approved.');
        }

        // ---------- SINGLE ----------
        $data = $request->validate([
            'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            'paidAmount'    => ['nullable','numeric','min:0'],
            'recipt'        => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            $r = ShopRental::lockForUpdate()->findOrFail($id);

            if ($request->hasFile('recipt')) {
                $path = $request->file('recipt')->store('receipts', 'public');
                $r->recipt = $path;
            }

            // If CASH and no amount provided, default to full bill
            $paid = $data['paidAmount'] ?? null;
            if ($data['paymentMethod'] === 'cash' && $paid === null) {
                $paid = (float) $r->billAmount;
            }

            if ($paid !== null) {
                $r->paidAmount = (float) $paid;
            }

            $r->paymentMethod = $data['paymentMethod'];
            $r->status        = 'Approved';
            $r->save();
        });

        return back()->with('success', 'Rental approved.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);

        $r = ShopRental::findOrFail($id);
        $r->status = 'Rejected';
        $r->save();

        return back()->with('success', 'Rental rejected.');
    }
}
