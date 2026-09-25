<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Task;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Tag;
use App\Models\AuditLog;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaaSApiController extends Controller {
 private function user(Request $r){return $r->attributes->get('apiUser');}
 private function workspace(Request $r){return $this->user($r)->workspace_id;}
 private function audit(Request $r,string $action,?string $type=null,?int $id=null,array $after=[]){AuditLog::create(['workspace_id'=>$this->workspace($r),'user_id'=>$this->user($r)->id,'action'=>$action,'entity_type'=>$type,'entity_id'=>$id,'after'=>$after,'ip'=>$r->ip()]);}
 public function companies(Request $r){return Company::where('workspace_id',$this->workspace($r))->latest()->paginate(30);}
 public function storeCompany(Request $r){$d=$r->validate(['name'=>'required|max:190','email'=>'nullable|email','phone'=>'nullable|max:30','website'=>'nullable|max:190','address'=>'nullable']);$d['workspace_id']=$this->workspace($r);$c=Company::create($d);$this->audit($r,'company.created','company',$c->id,$c->toArray());return response()->json($c,201);}
 public function tasks(Request $r){$q=Task::with(['assignee','subject'])->where('workspace_id',$this->workspace($r))->latest('due_at');if($r->filled('status'))$q->where('status',$r->string('status'));return $q->paginate(30);}
 public function storeTask(Request $r){$d=$r->validate(['title'=>'required|max:190','description'=>'nullable','priority'=>'nullable|max:20','due_at'=>'nullable|date','assigned_to'=>'nullable|exists:users,id','subject_type'=>'nullable|in:lead,contact,customer','subject_id'=>'nullable|integer']);if(isset($d['subject_type'])){$map=['lead'=>Lead::class,'contact'=>\App\Models\Contact::class,'customer'=>\App\Models\Customer::class];$d['subject_type']=$map[$d['subject_type']];}$d['workspace_id']=$this->workspace($r);$d['status']='pending';$t=Task::create($d);$this->audit($r,'task.created','task',$t->id,$t->toArray());return response()->json($t->load('assignee','subject'),201);}
 public function updateTask(Request $r,Task $task){abort_unless((int)$task->workspace_id===(int)$this->workspace($r),404);$d=$r->validate(['title'=>'nullable|max:190','description'=>'nullable','priority'=>'nullable|max:20','status'=>'nullable|max:30','due_at'=>'nullable|date','assigned_to'=>'nullable|exists:users,id']);$task->update($d);$this->audit($r,'task.updated','task',$task->id,$d);return $task->refresh();}
 public function tags(Request $r){return Tag::where('workspace_id',$this->workspace($r))->orderBy('name')->get();}
 public function storeTag(Request $r){$d=$r->validate(['name'=>'required|max:80','color'=>'nullable|max:20']);$tag=Tag::create(['workspace_id'=>$this->workspace($r),'name'=>$d['name'],'color'=>$d['color']??null]);$this->audit($r,'tag.created','tag',$tag->id,$tag->toArray());return response()->json($tag,201);}
 public function orders(Request $r){return Order::with(['customer.contact','items','payments'])->where('workspace_id',$this->workspace($r))->latest()->paginate(20);}
 public function storeOrder(Request $r){$d=$r->validate(['customer_id'=>'nullable|exists:customers,id','order_number'=>'nullable|max:80','status'=>'nullable|max:30','tax'=>'nullable|numeric','ordered_at'=>'nullable|date','notes'=>'nullable','items'=>'required|array|min:1','items.*.product_id'=>'nullable|exists:products,id','items.*.description'=>'required|max:190','items.*.qty'=>'required|numeric|min:0.01','items.*.unit_price'=>'required|numeric|min:0']);$total=0;DB::beginTransaction();try{$order=Order::create(['workspace_id'=>$this->workspace($r),'customer_id'=>$d['customer_id']??null,'order_number'=>$d['order_number']??('ORD-'.now()->format('YmdHis')),'status'=>$d['status']??'pending','tax'=>$d['tax']??0,'ordered_at'=>$d['ordered_at']??now()->toDateString(),'notes'=>$d['notes']??null]);foreach($d['items'] as $item){$line=$item['qty']*$item['unit_price'];$total+=$line;OrderItem::create($item+['order_id'=>$order->id,'line_total'=>$line]);}$order->update(['subtotal'=>$total,'total'=>$total+(float)$order->tax]);DB::commit();$this->audit($r,'order.created','order',$order->id,$order->toArray());return response()->json($order->load('items'),201);}catch(\Throwable $e){DB::rollBack();throw $e;}}
 public function payments(Request $r){return Payment::with(['customer.contact','order'])->where('workspace_id',$this->workspace($r))->latest('paid_at')->paginate(30);}
 public function storePayment(Request $r){$d=$r->validate(['customer_id'=>'nullable|exists:customers,id','order_id'=>'nullable|exists:orders,id','amount'=>'required|numeric|min:0.01','method'=>'required|max:30','reference'=>'nullable|max:190','status'=>'nullable|max:30','paid_at'=>'nullable|date','notes'=>'nullable']);$p=Payment::create($d+['workspace_id'=>$this->workspace($r),'status'=>$d['status']??'received','paid_at'=>$d['paid_at']??now()]);$this->audit($r,'payment.created','payment',$p->id,$p->toArray());return response()->json($p,201);}
 public function importLeads(Request $r){$data=$r->validate(['rows'=>'required|array|max:5000']);$created=0;foreach($data['rows'] as $row){if(empty($row['first_name'])||empty($row['mobile']))continue;Lead::create(['workspace_id'=>$this->workspace($r),'first_name'=>$row['first_name'],'last_name'=>$row['last_name']??null,'company'=>$row['company']??null,'email'=>$row['email']??null,'mobile'=>$row['mobile'],'whatsapp'=>$row['whatsapp']??$row['mobile'],'source'=>$row['source']??'import','status'=>$row['status']??'new','requirement'=>$row['requirement']??null,'notes'=>$row['notes']??null,'assigned_to'=>$this->user($r)->role==='sales'?$this->user($r)->id:($row['assigned_to']??null),'created_by'=>$this->user($r)->id]);$created++;} $this->audit($r,'lead.import','lead',null,['created'=>$created]);return ['created'=>$created];}
}
