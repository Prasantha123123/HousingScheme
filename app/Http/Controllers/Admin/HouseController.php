<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseController extends Controller
{
    /** List houses */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));

        $rows = House::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('houseNo', 'like', "%{$q}%")
                      ->orWhere('HouseOwneId', 'like', "%{$q}%")
                      ->orWhereHas('owner', function ($o) use ($q) {
                          $o->where('name', 'like', "%{$q}%");
                      });
                });
            })
            ->orderBy('houseNo')
            ->paginate(15)
            ->through(function (House $h) {
                $latest = HouseRental::where('houseNo', $h->houseNo)
                    ->orderByDesc('timestamp')
                    ->first();

                $h->owner_name         = optional($h->owner)->name ?? $h->HouseOwneId;
                $h->latest_bill_month  = optional($latest)->month;
                $h->latest_bill_amount = optional($latest)->billAmount;
                $h->latest_status      = optional($latest)->status ?? 'Pending';

                return $h;
            });

        return view('admin.houses.index', compact('rows'));
    }

    /** Create form */
    public function create()
    {
        $owners = User::where('role', 'Houseowner')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.houses.create', compact('owners'));
    }

    /** Store new house (one house per owner enforced here) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'houseNo'     => ['required', 'string', 'max:50', Rule::unique('houses', 'houseNo')],
            'HouseOwneId' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Houseowner')),
                Rule::unique('houses', 'HouseOwneId'), // owner can appear only once
            ],
        ], [
            'HouseOwneId.unique' => 'This owner already has a house.',
        ]);

        House::create($data);

        return redirect()->route('admin.houses.index')->with('success', 'House created.');
    }

    /** Edit form */
    public function edit(string $houseNo)
    {
        $house  = House::findOrFail($houseNo);
        $owners = User::where('role', 'Houseowner')->orderBy('name')->get(['id', 'name']);

        return view('admin.houses.edit', compact('house', 'owners'));
    }

    /** Update owner (still enforce one house per owner) */
    public function update(Request $request, string $houseNo)
    {
        $house = House::findOrFail($houseNo);

        $data = $request->validate([
            'HouseOwneId' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Houseowner')),
                // allow the same house to keep its current owner, block reassigning to an owner already used by another house
                Rule::unique('houses', 'HouseOwneId')->ignore($house->houseNo, 'houseNo'),
            ],
        ], [
            'HouseOwneId.unique' => 'This owner already has a house.',
        ]);

        $house->update($data);

        return redirect()->route('admin.houses.index')->with('success', 'House updated.');
    }

    /** Delete (optional guard if bills exist) */
    public function destroy(string $houseNo)
    {
        $house = House::findOrFail($houseNo);

        if (HouseRental::where('houseNo', $houseNo)->exists()) {
            return back()->withErrors('Cannot delete: bills exist for this house.');
        }

        $house->delete();

        return redirect()->route('admin.houses.index')->with('success', 'House deleted.');
    }

    /** Show one house bills */
    public function show(Request $r, string $houseNo)
    {
        $rentals = HouseRental::where('houseNo', $houseNo)
            ->orderByDesc('month')
            ->paginate(20);

        return view('admin.houses.show', compact('rentals', 'houseNo'));
    }
}
