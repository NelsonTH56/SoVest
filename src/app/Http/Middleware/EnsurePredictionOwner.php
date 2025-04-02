<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Prediction;

class EnsurePredictionOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the prediction ID from the route parameters
        $predictionId = $request->route('id');
        
        // Check if a prediction ID exists
        if (!$predictionId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Prediction not found'], 404);
            }
            
            return redirect()->back()->with('error', 'Prediction not found');
        }
        
        // Find the prediction in the database
        $prediction = Prediction::where('prediction_id', $predictionId)->first();
        
        // Check if the prediction exists
        if (!$prediction) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Prediction not found'], 404);
            }
            
            return redirect()->back()->with('error', 'Prediction not found');
        }
        
        // Check if the authenticated user is the owner of the prediction
        if ($prediction->user_id !== Auth::id()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to modify this prediction'], 403);
            }
            
            return redirect()->back()->with('error', 'You do not have permission to modify this prediction');
        }
        
        // If we reach here, the user is the owner, so allow the request to proceed
        return $next($request);
    }
}