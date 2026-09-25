<?php
namespace App\Http\Controllers;
use App\Models\Payment;use App\Models\Customer;use Illuminate\Http\Request;
class PaymentController extends Controller{
public function index(){return view('payments.index',['payments'=>Payment::with('customer')->latest('paid_at')->paginate(30),'customers'=>Customer::with('contact')->latest()->get()]);}
public function store(Request $r){$d=$r->validate(['customer_id'=>'required|exists:customers,id','order_id'=>'nullable|exists:orders,id','reference'=>'nullable|max:100','amount'=>'required|numeric|min:0.01','method'=>'required|in:cash,upi,bank,card,other','paid_at'=>'required|date','notes'=>'nullable']);Payment::create($d);return back()->with('success','Payment recorded.');}
}