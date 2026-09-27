<?php
namespace App\Http\Controllers;
use App\Models\Payment;use App\Models\Customer;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
class PaymentController extends Controller{
 public function index(){return view('payments.index',['payments'=>Payment::with('customer')->latest('paid_at')->paginate(30),'customers'=>Customer::with('contact')->latest()->get()]);}
 public function store(Request $r){$workspace=$r->attributes->get('workspace');$d=$r->validate(['customer_id'=>['required',Rule::exists('customers','id')->where(fn($q)=>$q->where('workspace_id',$workspace->id))],'order_id'=>['nullable',Rule::exists('orders','id')->where(fn($q)=>$q->where('workspace_id',$workspace->id))],'reference'=>'nullable|max:100','amount'=>'required|numeric|min:0.01','method'=>'required|in:cash,upi,bank,card,other','paid_at'=>'required|date','notes'=>'nullable']);Payment::create($d+['workspace_id'=>$workspace->id]);return back()->with('success','Payment recorded.');}
}
