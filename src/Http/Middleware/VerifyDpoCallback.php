<?php

namespace Mak8Tech\DpoPayments\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyDpoCallback
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verify the request is from DPO
        $token = $request->get('TransToken');
        $companyRef = $request->get('CompanyRef');

        if (!$token && !$companyRef) {
            Log::warning('Invalid DPO callback', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            abort(403, 'Unauthorized');
        }

        // Additional IP whitelist check if configured
        $allowedIps = config('dpo.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning('DPO callback from unauthorized IP', [
                'ip' => $request->ip(),
            ]);

            abort(403, 'Unauthorized IP');
        }

        return $next($request);
    }
}
