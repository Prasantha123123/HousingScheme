<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sewerage_charge' => ['required', 'numeric', 'min:0'],
            'water_unit_price' => ['required', 'numeric', 'min:0'],
            'service_charge' => ['required', 'numeric', 'min:0'], // NEW
            'bill_due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        Setting::put('sewerage_charge', (float) $data['sewerage_charge']);
        Setting::put('water_unit_price', (float) $data['water_unit_price']);
        Setting::put('service_charge', (float) $data['service_charge']); // NEW
        Setting::put('bill_due_day', $data['bill_due_day'] ?? null);

        return back()->with('success', 'Settings saved.');
    }
}
