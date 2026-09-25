<?php
namespace App\Http\Middleware;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CrmWebAuth {
 public function handle(Request $request,Closure $next):Response{$id=$request->session()->get('crm_user_id');if(!$id||!($user=User::find($id)))return redirect()->route('login');$request->attributes->set('crmUser',$user);return $next($request);}
}
