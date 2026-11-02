<?php

namespace App\Http\Controllers;

use App\Models\QuoteDraft;
use App\Notifications\StatusUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/** SOW: Get a Quote → Retrieve a Saved Quote. */
class QuoteDraftController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:filter', 'max:255'],
            'state' => ['required', 'array'],
            'step' => ['required', 'integer', 'between:0,20'],
        ]);

        $draft = QuoteDraft::create(['email' => strtolower($data['email']), 'state' => $data['state'], 'step' => $data['step']]);
        $link = route('quote', ['resume' => $draft->code]);

        // The draft is saved and its code is shown on screen, so a delivery problem is only logged
        try {
            Notification::route('mail', $draft->email)->notify(new StatusUpdate('Your saved COSPARK quote', $data['state']['first'] ?? 'there', [
                'Your quote request has been saved. Continue any time in the next '.QuoteDraft::DAYS_VALID.' days.',
                "Or choose Get a Quote → Retrieve a saved quote and enter code {$draft->code} with this email address.",
            ], ['Continue my quote', $link]));
        } catch (Throwable $e) {
            Log::error("Failed to email saved quote {$draft->code}", ['exception' => $e]);
        }

        return response()->json(['code' => $draft->code], 201);
    }

    public function create(): View
    {
        return view('pages.quote-retrieve');
    }

    public function retrieve(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'string', 'max:12']]);

        $draft = QuoteDraft::findValid($data['code'], $data['email'])
            ?? throw ValidationException::withMessages(['code' => 'We could not find a saved quote with that email and code. Saved quotes are kept for '.QuoteDraft::DAYS_VALID.' days.']);

        return redirect()->route('quote', ['resume' => $draft->code]);
    }
}
