<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Services\Interfaces\ResponseFormatterInterface;

/**
 * Auth Controller
 * 
 * Handles authentication and user registration.
 */
class MainController extends Controller
{
    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }

    /**
     * Display the landing page for guests
     * 
     * This method renders the main landing page for unauthenticated users.
     * For authenticated users, redirects to the home dashboard using modern routing.
     * 
     * @return void
     */
    public function index()
    {
        // If user is already logged in, redirect to home
        if (Auth::check()) {
            // Use route name instead of direct file reference
            return redirect()->route('user.home');
        }
        
        // Display the landing page with data
        return view('main');
    }

    public function about()
    {
        return view('about');
    }
}