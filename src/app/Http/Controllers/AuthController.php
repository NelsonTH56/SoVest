<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\ResponseFormatterInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use Exception;

/**
 * Auth Controller
 * 
 * Handles authentication and user registration.
 */
class AuthController extends Controller
{
    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }

    /**
     * Display login form
     * 
     * @return void
     */
    public function loginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('user.home');
        }
        return view('login');
    }

    /**
     * Handle login form submission
     * 
     * @return void
     */
    public function login(Request $request): RedirectResponse
    {
        // Validate the input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.'
        ]);

        try {
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                return redirect()->route('home');
            }
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        } catch (Exception $e) {
            // Log the error
            error_log("Login error: " . $e->getMessage());

            // Redirect to login page with a generic error
            return redirect()->route('login', ['error' => 'system_error']);
        }
    }

    public function registerForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('user.home');
        }
        return view('register');
    }

    /**
     * Handle registration form submission
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function register(Request $request)
    {
        // Validate the input with Laravel's validation system
        $validated = $request->validate([
            'firstName' => ['required'],
            'lastName' => ['required'],
            'newEmail' => ['required', 'email', 'unique:users,email'],
            'newPass' => ['required', 'min:8'],
            'confirmPass' => ['required', 'same:newPass'],
            'newMajor' => ['required'],
            'newYear' => ['required'],
            'newScholarship' => ['required']
        ], [
            'firstName.required' => 'First name is required.',
            'lastName.required' => 'Last name is required.',
            'newEmail.required' => 'Email is required.',
            'newEmail.email' => 'Please enter a valid email address.',
            'newEmail.unique' => 'This email is already registered.',
            'newPass.required' => 'Password is required.',
            'confirmPass.same' => 'Passwords do not match.',
            'newPass.min' => 'Password must be at least 8 characters long.',
            'newMajor.required' => 'Major is required.',
            'newYear.required' => 'Year is required.',
            'newScholarship.required' => 'Scholarship information is required.'
        ]);

        try {
            // Create user directly using Laravel's User model
            $user = User::create([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'email' => $validated['newEmail'],
                'password' => Hash::make($validated['newPass']),
                'major' => $validated['newMajor'],
                'year' => $validated['newYear'],
                'scholarship' => $validated['newScholarship']
            ]);

            // Log the user in using Auth facade
            Auth::login($user);

            if (Auth::check()) {
                $request->session()->regenerate();

                return redirect()->route('user.home');
            }

            // Redirect to login page with success message for web request
            return redirect()->route('login', ['success' => 1]);

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'system_error'])->withInput();
        }
    }

    /**
     * Handle user logout
     * 
     * @return void
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}