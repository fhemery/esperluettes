<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Auth\Private\Requests\ParentalAuthorizationRequest;
use App\Domains\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    /**
     * Show the terms and conditions acceptance page.
     */
    public function showTerms(): View
    {
        return view('auth::pages.compliance.terms');
    }

    /**
     * Handle terms and conditions acceptance.
     */
    public function acceptTerms(Request $request): RedirectResponse
    {
        $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ]);

        /** @var \App\Domains\Auth\Private\Models\User $user */
        $user = Auth::user();
        $user->acceptTerms();

        // Clear the compliance check cache to re-evaluate
        session()->forget('user_compliance_checked_' . $user->id);

        // Check if user still needs parental authorization
        if ($user->needsParentalAuthorization()) {
            return redirect()->route('compliance.parental.show');
        }

        // Redirect to intended URL or dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show the parental authorization upload page.
     */
    public function showParentalAuthorization(): View
    {
        /** @var \App\Domains\Auth\Private\Models\User $user */
        $user = Auth::user();

        // Redirect to terms if not yet accepted
        if (!$user->hasAcceptedTerms()) {
            return view('auth::pages.compliance.terms');
        }

        // Redirect to dashboard if not underage or already verified
        if (!$user->is_under_15 || !$user->needsParentalAuthorization()) {
            return redirect()->route('dashboard');
        }

        return view('auth::pages.compliance.parental');
    }

    /**
     * Handle parental authorization upload.
     */
    public function uploadParentalAuthorization(ParentalAuthorizationRequest $request): RedirectResponse
    {

        /** @var \App\Domains\Auth\Private\Models\User $user */
        $user = Auth::user();

        if (!$user->is_under_15) {
            return redirect()->route('dashboard');
        }

        // Store the file
        $path = $request->file('parental_authorization')->store('parental_authorizations', 'private');

        // TODO: Store the file path in a separate table or user metadata
        // For now, we just mark it as verified (you may want to add admin verification)
        
        // Only mark as verified for underage users
        if ($user->is_under_15) {
            $user->verifyParentalAuthorization();
        }

        // Clear the compliance check cache
        session()->forget('user_compliance_checked_' . $user->id);

        // Redirect to intended URL or dashboard
        return redirect()->intended(route('dashboard'))
            ->with('success', 'Parental authorization uploaded successfully. Your account is now fully active.');
    }
}
