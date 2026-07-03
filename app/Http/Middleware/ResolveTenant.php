<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant (Organization) from the request host so the login
 * page and portals can show the client's own branding, and logins can be
 * restricted to the tenant they belong to.
 *
 * Resolution order:
 *   1. (non-production only) ?tenant=slug query — convenience for local testing
 *      where wildcard subdomains may not resolve.
 *   2. Exact custom domain match (organizations.domain = host).
 *   3. Subdomain under the configured base domain (organizations.slug = label).
 *
 * The resolved org (or null) is bound as `tenant` in the container and shared
 * with every view.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        // Only bind when present: binding null makes the container try to *build*
        // a class named "tenant" on the next app('tenant') call.
        if ($tenant) {
            app()->instance('tenant', $tenant);
        } else {
            app()->forgetInstance('tenant');
        }
        View::share('tenant', $tenant);

        return $next($request);
    }

    protected function resolve(Request $request): ?Organization
    {
        // 1. Dev convenience override.
        if (! app()->environment('production') && ($slug = $request->query('tenant'))) {
            return Organization::where('slug', $slug)->first();
        }

        $host = $request->getHost();

        // 2. Full custom domain (e.g. mentoring.lasbambas.com).
        if ($org = Organization::where('domain', $host)->first()) {
            return $org;
        }

        // 3. Subdomain under the base domain.
        $base = config('tenancy.base_domain');
        if ($base && $host !== $base && str_ends_with($host, '.'.$base)) {
            $label = explode('.', substr($host, 0, -(strlen($base) + 1)))[0];

            if ($label === '' || in_array($label, config('tenancy.central_subdomains', []), true)) {
                return null;
            }

            return Organization::where('slug', $label)->first();
        }

        return null;
    }
}
