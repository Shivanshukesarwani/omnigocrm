<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Task;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class MobileApiController extends Controller{
public function companies(){return Company::latest()->paginate(30);}
public function storeCompany(Request $r){$d=$r->validate(['name'=>'required|max:180','email'=>'nullable|email','phone'=>'nullable|max:30','website'=>'nullable|url','address'=>'nullable']);return response()->json(Company::create($d),201);}
public function tasks(Request $r){$q=Task::with('assignee')->latest('due_at');if($r->filled('status'))$q->where('status',$r->status);return $q->paginate(30);}
public function storeTask(Request $r){$d=$r->validate(['title'=>'required|max:180','description'=>'nullable','due_at'=>'nullable|date','priority'=>'required|in:low,normal,high,urgent','assigned_to'=>'nullable|exists:users,id']);return response()->json(Task::create($d+['status'=>'open']),201);}
public function completeTask(Task $task){$task->update(['status'=>'completed']);return ['task'=>$task];}
public function products(){return Product::where('active',true)->orderBy('name')->paginate(50);}
public function quotations(){return Quotation::with(['customer.contact','items'])->latest()->paginate(30);}
public function orders(){return Order::with(['customer.contact','items','payments'])->latest()->paginate(30);}
public function payments(){return Payment::with(['customer.contact','order'])->latest('paid_at')->paginate(30);}
public function notifications(Request $r){return $r->attributes->get('apiUser')->notifications()->latest()->paginate(30);}
public function readNotification(Request $r,string $id){$n=$r->attributes->get('apiUser')->notifications()->findOrFail($id);$n->markAsRead();return ['message'=>'Notification marked read'];}
}