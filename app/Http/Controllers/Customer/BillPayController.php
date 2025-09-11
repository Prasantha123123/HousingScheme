<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPayController extends Controller
{
    public function transfer(Request $r, int $id)
    {
        $r->validate([
            'reference' => ['required','string','max:100'],
            'recipt'    => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
            'note'      => ['nullable','string','max:500'],
        ]);

        $latest      = HouseRental::findOrFail($id);
        $receiptPath = $r->file('recipt')->store('receipts', 'public');

        return $this->settleUpToLatestAndRedirect($latest, 'online', $receiptPath);
    }

    public function card(Request $r, int $id)
    {
        $latest = HouseRental::findOrFail($id);
        return $this->settleUpToLatestAndRedirect($latest, 'card', null);
    }

    protected function settleUpToLatestAndRedirect(HouseRental $latest, string $method, ?string $receiptPath)
    {
        // Only the latest unpaid bill can be paid
        $hasLaterUnpaid = HouseRental::where('houseNo', $latest->houseNo)
            ->where('month', '>', $latest->month)
            ->whereColumn('paidAmount', '<', 'billAmount')
            ->exists();

        if ($hasLaterUnpaid) {
            return back()->withErrors('You can only pay the latest outstanding bill.');
        }

        DB::transaction(function () use ($latest, $method, $receiptPath) {
            $rows = HouseRental::where('houseNo', $latest->houseNo)
                ->where('month', '<=', $latest->month)
                ->whereColumn('paidAmount', '<', 'billAmount')
                ->orderBy('month')
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                // Per-month normalization: each row’s paid equals that row’s bill
                $row->paidAmount    = $row->billAmount;
                $row->paymentMethod = $method;
                if ($row->id === $latest->id && $receiptPath) {
                    $row->recipt = $receiptPath; // receipt attached to the latest row
                }
                $row->status = 'Pending';
                $row->save();
            }
        });

        return back()->with('success', 'Payment recorded. Awaiting admin approval.');
    }
}
