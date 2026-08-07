<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Handle an incoming request and initialize tenant context.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            if (! $user->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                abort(403, __('Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'));
            }

            $membership = $user->activeMembership();

            if ($membership) {
                $company = $membership->company;
                if (! $membership->isActive() || ! $company || ! $company->isActive()) {
                    abort(403, __('Keanggotaan atau Perusahaan Anda dalam status non-aktif.'));
                }

                setPermissionsTeamId($membership->company_id);
            } elseif (! $user->isSuperAdmin()) {
                abort(403, __('Akun Anda belum memiliki keanggotaan perusahaan yang aktif.'));
            }
        }

        return $next($request);
    }
}
