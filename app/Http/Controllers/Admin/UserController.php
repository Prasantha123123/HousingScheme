<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(){ $rows = User::orderBy('name')->paginate(20); return view('admin.users.index', compact('rows')); }
    public function create(){ return view('admin.users.create'); }
    public function store(Request $r){
        $data = $r->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users',
            'role'=>'required|in:Admin,Houseowner,Merchant,Employee',
            'address'=>'nullable|string|max:255',
            'NIC'=>'nullable|string|max:100',
            'password'=>'required|string|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('admin.users.index')->with('success','User created');
    }
    public function edit(User $user){ return view('admin.users.edit', ['row'=>$user]); }
    public function update(Request $r, User $user){
        $data = $r->validate([
            'name'=>'required|string|max:255',
            'email'=>"required|email|unique:users,email,{$user->id}",
            'role'=>'required|in:Admin,Houseowner,Merchant,Employee',
            'address'=>'nullable|string|max:255',
            'NIC'=>'nullable|string|max:100',
        ]);
        $user->update($data);
        return back()->with('success','Updated');
    }
}
