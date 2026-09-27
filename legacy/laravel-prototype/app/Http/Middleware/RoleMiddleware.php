<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RoleMiddleware{
public function handle(Request $request,Closure $next,...$roles):Response{
$user=$request->attributes->get('crmUser');
abort_unless($user && in_array($user->role,$roles,true),403,'You do not have permission to access this area.');
return $next($request);
}}