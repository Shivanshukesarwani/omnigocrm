<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class NotificationController extends Controller{
public function index(Request $r){$user=$r->attributes->get('crmUser');return view('notifications.index',['notifications'=>$user->notifications()->latest()->paginate(30)]);}
public function read(Request $r,string $id){$n=$r->attributes->get('crmUser')->notifications()->findOrFail($id);$n->markAsRead();return back();}
}