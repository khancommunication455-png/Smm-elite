<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * LoginController
 *
 * FIXES:
 * - CRITICAL-4: Open redirect via redirect_to parameter — now validates to local paths only
 * - Added proper session regeneration
 * - Rate limiting is applied via route definition (throttle:login)
 */
class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|min:8|max:128',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Failed login attempt', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->status === 'banned') {
            Auth::logout();
            $request->session()->invalidate();
            Log::warning('Banned user attempted login', ['email' => $user->email, 'ip' => $request->ip()]);

            return back()
                ->withInput()
                ->withErrors(['email' => 'This account has been suspended. Contact support.']);
        }

        Log::info('User logged in', ['user_id' => $user->id, 'ip' => $request->ip()]);

        // FIXED: Validate redirect_to is a relative local path only — prevents open redirect
        $intended = $request->input('redirect_to', '');
        if (! $this->isSafeRedirect($intended)) {
            $intended = route('dashboard');
        }

        return redirect()->intended($intended);
    }

    public function logout(Request $request)
    {
        Log::info('User logged out', ['user_id' => Auth::id()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Only allow same-origin relative redirects.
     * Blocks: https://evil.com, //evil.com, javascript:alert(1)
     */
    private function isSafeRedirect(string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        // Must start with a single slash (relative path), no protocol or double-slash
        return (bool) preg_match('#^/(?!/)#', $url);
    }
}
