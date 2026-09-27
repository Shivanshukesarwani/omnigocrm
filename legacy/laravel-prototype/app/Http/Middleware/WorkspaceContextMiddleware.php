<?php
namespace App\Http\Middleware;
use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class WorkspaceContextMiddleware{
public function handle(Request $request,Closure $next):Response{
$user=$request->attributes->get('crmUser')??$request->attributes->get('apiUser');
if(!$user||!$user->workspace_id)return response()->json(['message'=>'Workspace is not configured'],422);
$workspace=$user->workspace;
abort_unless($workspace && $workspace->status==='active',403,'Workspace inactive.');
app(WorkspaceContext::class)->set($workspace);
$request->attributes->set('workspace',$workspace);
return $next($request);
}}