<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\SetupCompanyForUser;
use App\Concerns\CompanyValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialiteController extends Controller
{
    use CompanyValidationRules;

    private const PENDING_USER_SESSION_KEY = 'google_signin.user_id';

    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirectToGoogle(Request $request): SymfonyRedirectResponse|Response
    {
        // Browsers (via Chrome's Speculation Rules API -- e.g. Cloudflare's
        // "Speed Brain" feature auto-enables this for every same-origin link)
        // may speculatively prefetch this GET link before the user actually
        // clicks it. Socialite::redirect() mutates the session (stores a
        // fresh OAuth "state" nonce) on every hit, so a speculative hit races
        // the real click and desyncs the state Google echoes back from what's
        // in the session by the time the callback arrives -- surfacing as
        // InvalidStateException for a user who did nothing wrong. Chrome
        // marks speculative requests with Sec-Purpose: prefetch[;prerender];
        // skip the side-effecting redirect for those.
        if (str_contains((string) $request->header('Sec-Purpose'), 'prefetch')) {
            return response()->noContent();
        }

        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');

        return $provider
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     *
     * Existing users are logged in directly. New users are provisioned (without
     * a company) and sent to a completion page to create their tenant.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');

        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = $provider->user();
        } catch (InvalidStateException $e) {
            // OAuth state did not match, usually because the session cookie was
            // lost between the redirect and the callback (e.g. stale browser
            // cookie or a new session). Send the user back to retry.
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('Your Google sign-in session expired. Please try again.'),
            ]);
        }

        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        // SECURITY-RULES 4.2: only allow verified Google accounts.
        if (! $email || (($googleUser->user['email_verified'] ?? false) !== true)) {
            return redirect()->route('login')->withErrors([
                'email' => __('Your Google account email must be verified to sign in.'),
            ]);
        }

        $user = $this->resolveUser($googleId, $email);

        if ($user) {
            if ($user->status !== 'active') {
                return redirect()->route('login')->withErrors([
                    'email' => __('Your account has been deactivated.'),
                ]);
            }

            if (! $user->google_id) {
                $user->forceFill(['google_id' => $googleId])->save();
            }

            // A user row can exist with no membership at all when a prior
            // Google sign-in created the account but never finished company
            // setup (e.g. it failed after this point). Route them back to
            // setup instead of logging them into a dashboard the tenant
            // context middleware would immediately 403.
            if (! $user->isSuperAdmin() && $user->warehouseMemberships()->doesntExist()) {
                Session::put(self::PENDING_USER_SESSION_KEY, $user->id);

                return redirect()->route('auth.google.complete');
            }

            Auth::login($user);

            return redirect()->intended(route('dashboard'));
        }

        // Provision a pending user and ask them to create their company.
        $user = User::create([
            'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: $email),
            'email' => $email,
            'google_id' => $googleId,
            'password' => Str::random(40),
            'email_verified_at' => now(),
            'status' => 'active',
            'is_super_admin' => false,
        ]);

        Session::put(self::PENDING_USER_SESSION_KEY, $user->id);

        return redirect()->route('auth.google.complete');
    }

    /**
     * Show the company setup form for a newly created Google user.
     */
    public function showCompleteForm(): RedirectResponse|View
    {
        if (! $this->pendingUserId()) {
            return redirect()->route('login');
        }

        return view('pages::auth.google-complete');
    }

    /**
     * Create the user's company, warehouse, and membership, then log them in.
     */
    public function completeCompany(Request $request): RedirectResponse
    {
        $pendingUserId = $this->pendingUserId();

        if (! $pendingUserId) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($pendingUserId);

        if (! $user) {
            Session::forget(self::PENDING_USER_SESSION_KEY);

            return redirect()->route('login')->withErrors([
                'email' => __('Unable to complete your Google sign-in. Please try again.'),
            ]);
        }

        $validated = Validator::make($request->all(), [
            'company_name' => $this->companyNameRules(),
            'company_code' => ['nullable', 'string', 'max:50'],
        ])->validate();

        app(SetupCompanyForUser::class)->create($user, $validated['company_name'], $validated['company_code'] ?? null);

        Session::forget(self::PENDING_USER_SESSION_KEY);
        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }

    private function resolveUser(string $googleId, string $email): ?User
    {
        return User::query()
            ->where(function ($query) use ($googleId, $email) {
                $query->where('google_id', $googleId)
                    ->orWhere('email', $email);
            })
            ->first();
    }

    private function pendingUserId(): ?int
    {
        return Session::get(self::PENDING_USER_SESSION_KEY);
    }
}
