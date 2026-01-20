<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Services\Interfaces\ResponseFormatterInterface;

/**
 * Feedback Controller
 *
 * Handles user feedback submission and email sending.
 */
class FeedbackController extends Controller
{
    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }

    /**
     * Display the feedback form
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $Curruser = Auth::user();
        return view('feedback', compact('Curruser'));
    }

    /**
     * Process and send the feedback email
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:suggestion,bug,feature,general',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:10|max:2000',
        ]);

        $user = Auth::user();
        $userName = $user ? $user->first_name . ' ' . $user->last_name : 'Anonymous';
        $userEmail = $user ? $user->email : 'Not logged in';

        $typeLabels = [
            'suggestion' => 'Suggestion',
            'bug' => 'Bug Report',
            'feature' => 'Feature Request',
            'general' => 'General Feedback',
        ];

        $feedbackType = $typeLabels[$validated['type']] ?? 'Feedback';

        $emailBody = "New Feedback from SoVest\n";
        $emailBody .= "========================\n\n";
        $emailBody .= "Type: {$feedbackType}\n";
        $emailBody .= "From: {$userName}\n";
        $emailBody .= "Email: {$userEmail}\n";
        $emailBody .= "Subject: {$validated['subject']}\n\n";
        $emailBody .= "Message:\n";
        $emailBody .= "--------\n";
        $emailBody .= $validated['message'];

        try {
            Mail::raw($emailBody, function ($message) use ($validated, $feedbackType, $userEmail) {
                $message->to('tech.sovest.co@gmail.com')
                    ->subject("[SoVest {$feedbackType}] {$validated['subject']}");

                // If user is logged in, set reply-to their email
                if ($userEmail !== 'Not logged in') {
                    $message->replyTo($userEmail);
                }
            });

            return redirect()->route('feedback')
                ->with('success', 'Thank you for your feedback! We\'ll review it soon.');
        } catch (\Exception $e) {
            return redirect()->route('feedback')
                ->with('error', 'There was an issue sending your feedback. Please try again later.')
                ->withInput();
        }
    }
}
