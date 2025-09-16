<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\House;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required','string','max:255'],
            'password'   => ['required','string'],
        ]);

        $identifier = trim($data['identifier']);
        $password   = $data['password'];

        $user = $this->findUserByIdentifier($identifier);

        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['identifier' => 'Invalid credentials.'])
                ->onlyInput('identifier');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    /**
     * Resolve a user by:
     *  1) email exactly
     *  2) Admin by name
     *  3) Houseowner by House No (Houses.houseNo -> Houses.HouseOwneId)
     *  4) Merchant by Shop No (Shops.shopNumber -> Shops.MerchantId)
     */
    protected function findUserByIdentifier(string $identifier): ?User
    {
        // 1) Email
        $user = User::where('email', $identifier)->first();
        if ($user) return $user;

        // 2) Admin by name
        $user = User::where('role', 'Admin')->where('name', $identifier)->first();
        if ($user) return $user;

        // 3) Houseowner via Houses table
        $ownerId = House::where('houseNo', $identifier)->value('HouseOwneId');
        if ($ownerId) {
            $user = User::where('id', $ownerId)->where('role', 'Houseowner')->first();
            if ($user) return $user;
        }

        // 4) Merchant via Shops table
        $merchantId = Shop::where('shopNumber', $identifier)->value('MerchantId');
        if ($merchantId) {
            $user = User::where('id', $merchantId)->where('role', 'Merchant')->first();
            if ($user) return $user;
        }

        return null;
    }
}
