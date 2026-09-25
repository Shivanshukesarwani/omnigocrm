<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OrderController extends Controller{
public function index(){return view('orders.index',['orders'=>Order::with('customer.contact')->latest()->paginate(25),'customers'=>Customer::with('contact')->latest()->get()]);}
public function store(Request $r){
$d=$r->validate(['customer_id'=>'required|exists:customers,id','tax'=>'nullable|numeric|min:0','order_date'=>'nullable|date','items'=>'required|array|min:1','items.*.description'=>'required|max:200','items.*.qty'=>'required|numeric|min:0.01','items.*.unit_price'=>'required|numeric|min:0']);
$o=null;DB::transaction(function()use($d,&$o){$subtotal=collect($d['items'])->sum(fn($i)=>(float)$i['qty']*(float)$i['unit_price']);$tax=(float)($d['tax']??0);$o=Order::create(['customer_id'=>$d['customer_id'],'order_number'=>'ORD-'.now()->format('Ymd').'-'.strtoupper(str()->random(6)),'status'=>'pending','subtotal'=>$subtotal,'tax'=>$tax,'total'=>$subtotal+$tax,'order_date'=>$d['order_date']??now()->toDateString()]);foreach($d['items'] as $i)OrderItem::create(['order_id'=>$o->id,'description'=>$i['description'],'qty'=>$i['qty'],'unit_price'=>$i['unit_price'],'line_total'=>(float)$i['qty']*(float)$i['unit_price']]);});
return redirect()->route('orders.show',$o)->with('success','Order created.');
}
public function show(Order $order){return view('orders.show',['order'=>$order,'items'=>OrderItem::where('order_id',$order->id)->get()]);}
}