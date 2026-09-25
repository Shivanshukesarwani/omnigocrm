<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user=$request->attributes->get('crmUser');
        if (!$user || !in_array($user->role, $roles, true)) abort(403, 'You do not have permission to access this area.');
        return $next($request);
    }
}
