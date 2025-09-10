<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit(){
        return view('admin.settings.edit');
    }

    public function update(Request $r){
        $data = $r->validate([
            'sewerage_charge'=>'required|numeric|min:0',
            'water_unit_price'=>'required|numeric|min:0',
            'bill_due_day'=>'nullable|integer|min:1|max:31',
        ]);
        Setting::setVal('sewerage_charge', $data['sewerage_charge']);
        Setting::setVal('water_unit_price', $data['water_unit_price']);
        if (array_key_exists('bill_due_day',$data)) Setting::setVal('bill_due_day', $data['bill_due_day']);
        return back()->with('success','Settings saved');
    }
}
