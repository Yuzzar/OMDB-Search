<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('authenticated')) {
            return redirect()->route('movies.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $validUsername = config('static_credentials.username');
        $validPassword = config('static_credentials.password');

        if (
            $request->input('username') === $validUsername
            && $request->input('password') === $validPassword
        ) {
            session(['authenticated' => true, 'username' => $request->input('username')]);

            return redirect()->intended(route('movies.index'))
                ->with('success', __('app.login_success'));
        }

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['credentials' => __('app.invalid_credentials')]);
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('login')
            ->with('success', __('app.logout_success'));
    }
}
