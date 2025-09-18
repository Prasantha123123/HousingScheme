<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

                $h->owner_name         = optional($h->owner)->name ?? 'Unassigned';
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
            ->get(['id', 'name', 'email']);

        return view('admin.houses.create', compact('owners'));
    }

    /**
     * Store new house
     * - Owner optional
     * - If no owner, house_password is required
     * - One house per owner (when owner provided)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'houseNo'         => ['required', 'string', 'max:50', Rule::unique('houses', 'houseNo')],
            'HouseOwneId'     => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Houseowner')),
                Rule::unique('houses', 'HouseOwneId')->where(fn ($q) => $q->whereNotNull('HouseOwneId')),
            ],
            'house_password'  => ['required_without:HouseOwneId', 'nullable', 'string', 'min:6'],
        ], [
            'HouseOwneId.unique'            => 'This owner already has a house.',
            'house_password.required_without' => 'Set a house password when no owner is selected.',
        ]);

        if (!empty($data['house_password'])) {
            $data['house_password'] = Hash::make($data['house_password']);
        }

        House::create($data);

        return redirect()->route('admin.houses.index')->with('success', 'House created.');
    }

    /** Edit form */
    public function edit(string $houseNo)
    {
        $house  = House::findOrFail($houseNo);
        $owners = User::where('role', 'Houseowner')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.houses.edit', compact('house', 'owners'));
    }

    /**
     * Update house
     * - Owner can be cleared
     * - house_password updates only if provided
     * - Keep one house per owner (ignore current)
     */
    public function update(Request $request, string $houseNo)
    {
        $house = House::findOrFail($houseNo);

        $data = $request->validate([
            'HouseOwneId'     => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Houseowner')),
                Rule::unique('houses', 'HouseOwneId')
                    ->ignore($house->houseNo, 'houseNo')
                    ->where(fn ($q) => $q->whereNotNull('HouseOwneId')),
            ],
            'house_password'  => ['nullable', 'string', 'min:6'],
        ], [
            'HouseOwneId.unique' => 'This owner already has a house.',
        ]);

        if (!empty($data['house_password'])) {
            $data['house_password'] = Hash::make($data['house_password']);
        } else {
            unset($data['house_password']); // don't overwrite with null
        }

        $house->update($data);

        return redirect()->route('admin.houses.index')->with('success', 'House updated.');
    }

    /** Delete (guard if bills exist) */
    public function destroy(string $houseNo)
    {
        $house = House::findOrFail($houseNo);

        if (\App\Models\HouseRental::where('houseNo', $houseNo)->exists()) {
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
