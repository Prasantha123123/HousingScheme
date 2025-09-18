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

    /**
     * Multi-guard login flow (NO user creation/provisioning):
     * 
     * 1) Try normal user login (web guard) - email/admin-name/assigned house/shop
     * 2) Try house login (house guard) - only if HouseOwneId is NULL
     * 3) Try shop login (shop guard) - only if MerchantId is NULL
     * 
     * Redirect to guard-specific dashboards on success.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required','string','max:255'],
            'password'   => ['required','string'],
        ]);

        $identifier = trim($data['identifier']);
        $password   = $data['password'];

        // Clear any previous authentication state and intended URLs to prevent cross-guard interference
        Auth::guard('web')->logout();
        Auth::guard('house')->logout();
        Auth::guard('shop')->logout();
        $request->session()->forget('url.intended');

        // Debug logging
        \Log::info('Login attempt', [
            'identifier' => $identifier,
            'ip' => $request->ip(),
        ]);

        // 1) Try normal user login (web guard)
        if ($this->attemptUserLogin($identifier, $password, $request)) {
            \Log::info('User login successful', ['identifier' => $identifier]);
            return redirect()->route('dashboard');
        }

        // 2) Try house login (house guard) - only if unassigned
        if ($this->attemptHouseLogin($identifier, $password, $request)) {
            \Log::info('House login successful', ['identifier' => $identifier, 'redirect_to' => route('house.dashboard')]);
            return redirect()->route('house.dashboard');
        }

        // 3) Try shop login (shop guard) - only if unassigned  
        if ($this->attemptShopLogin($identifier, $password, $request)) {
            \Log::info('Shop login successful', ['identifier' => $identifier, 'redirect_to' => route('shop.dashboard')]);
            return redirect()->route('shop.dashboard');
        }

        // All authentication attempts failed
        \Log::warning('Login failed', ['identifier' => $identifier]);
        return back()
            ->withErrors(['identifier' => 'Invalid credentials.'])
            ->onlyInput('identifier');
    }

    /**
     * Logout from all guards and invalidate session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        \Log::info('Logout initiated', [
            'web_auth' => Auth::guard('web')->check(),
            'house_auth' => Auth::guard('house')->check(),
            'shop_auth' => Auth::guard('shop')->check(),
        ]);

        // Logout from all possible guards
        Auth::guard('web')->logout();
        Auth::guard('house')->logout();
        Auth::guard('shop')->logout();

        // Clear any intended URL and authentication data from session
        $request->session()->forget('url.intended');
        $request->session()->forget('login_web_*');
        $request->session()->forget('login_house_*');
        $request->session()->forget('login_shop_*');
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        \Log::info('Logout completed - session cleared');
        return redirect('/');
    }

    /**
     * Attempt login via web guard (normal user authentication).
     * Checks email, admin name, assigned house, assigned shop.
     */
    protected function attemptUserLogin(string $identifier, string $password, Request $request): bool
    {
        $user = $this->findUserByIdentifier($identifier);
        
        if ($user && Hash::check($password, $user->password)) {
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return true;
        }
        
        return false;
    }

    /**
     * Attempt login via house guard.
     * Only works if house exists, HouseOwneId is NULL, and password matches.
     */
    protected function attemptHouseLogin(string $identifier, string $password, Request $request): bool
    {
        $house = House::where('houseNo', $identifier)->first();
        
        \Log::info('House login attempt', [
            'identifier' => $identifier,
            'house_found' => $house ? 'yes' : 'no',
            'can_login_directly' => $house ? ($house->canLoginDirectly() ? 'yes' : 'no') : 'N/A',
            'house_owner_id' => $house ? $house->HouseOwneId : 'N/A',
            'has_password' => $house ? (!empty($house->house_password) ? 'yes' : 'no') : 'N/A'
        ]);
        
        if ($house && $house->canLoginDirectly()) {
            // Use the guard's attempt method for proper authentication
            $success = Auth::guard('house')->attempt([
                $house->getAuthIdentifierName() => $identifier,
                'password' => $password
            ], $request->boolean('remember'));
            
            \Log::info('House guard attempt result', [
                'identifier' => $identifier,
                'success' => $success ? 'yes' : 'no',
                'auth_name' => $house->getAuthIdentifierName()
            ]);
            
            if ($success) {
                $request->session()->regenerate();
                return true;
            }
        }
        
        return false;
    }

    /**
     * Attempt login via shop guard.
     * Only works if shop exists, MerchantId is NULL, and password matches.
     */
    protected function attemptShopLogin(string $identifier, string $password, Request $request): bool
    {
        $shop = Shop::where('shopNumber', $identifier)->first();
        
        if ($shop && $shop->canLoginDirectly()) {
            // Use the guard's attempt method for proper authentication
            $success = Auth::guard('shop')->attempt([
                $shop->getAuthIdentifierName() => $identifier,
                'password' => $password
            ], $request->boolean('remember'));
            
            if ($success) {
                $request->session()->regenerate();
                return true;
            }
        }
        
        return false;
    }

    /**
     * Resolve a user by various identifiers:
     *  1) Email exactly
     *  2) Admin by name
     *  3) Houseowner by assigned House No (Houses.houseNo -> Houses.HouseOwneId)
     *  4) Merchant by assigned Shop No (Shops.shopNumber -> Shops.MerchantId)
     */
    protected function findUserByIdentifier(string $identifier): ?User
    {
        // 1) Email
        $user = User::where('email', $identifier)->first();
        if ($user) return $user;

        // 2) Admin by name
        $user = User::where('role', 'Admin')->where('name', $identifier)->first();
        if ($user) return $user;

        // 3) Houseowner via Houses (assigned only)
        $ownerId = House::where('houseNo', $identifier)->value('HouseOwneId');
        if ($ownerId) {
            $user = User::where('id', $ownerId)->where('role', 'Houseowner')->first();
            if ($user) return $user;
        }

        // 4) Merchant via Shops (assigned only)
        $merchantId = Shop::where('shopNumber', $identifier)->value('MerchantId');
        if ($merchantId) {
            $user = User::where('id', $merchantId)->where('role', 'Merchant')->first();
            if ($user) return $user;
        }

        return null;
    }
}
