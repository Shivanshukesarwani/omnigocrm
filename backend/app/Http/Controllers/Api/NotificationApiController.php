<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
 public function index(Request $r){
  $u=$r->attributes->get('apiUser');
  return $u->notifications()->latest()->paginate(30);
 }

 public function read(Request $r,string $id){
  $u=$r->attributes->get('apiUser');
  $n=$u->notifications()->findOrFail($id);
  $n->markAsRead();
  return ['message'=>'Notification marked as read'];
 }
}
