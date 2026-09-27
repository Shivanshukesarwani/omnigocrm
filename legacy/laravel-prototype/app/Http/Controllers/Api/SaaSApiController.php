<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Call;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SaaSApiController extends Controller
{
 private function user(Request $r){return $r->attributes->get('apiUser');}
 private function workspace(Request $r){return $this->user($r)->workspace_id;}
 private function audit(Request $r,string $action,?string $type=null,?int $id=null,array $after=[]):void{
  AuditLog::create([
   'workspace_id'=>$this->workspace($r),
   'user_id'=>$this->user($r)->id,
   'action'=>$action,
   'entity_type'=>$type,
   'entity_id'=>$id,
   'after'=>$after,
   'ip'=>$r->ip(),
  ]);
 }
 private function existsInWorkspace(Request $r,string $table){return Rule::exists($table,'id')->where(fn($q)=>$q->where('workspace_id',$this->workspace($r)));}

 public function companies(Request $r){return Company::latest()->paginate(30);}

 public function storeCompany(Request $r){
  $d=$r->validate(['name'=>'required|max:190','email'=>'nullable|email','phone'=>'nullable|max:30','website'=>'nullable|max:190','address'=>'nullable']);
  $c=Company::create($d);
  $this->audit($r,'company.created','company',$c->id,$c->toArray());
  return response()->json($c,201);
 }

 public function tasks(Request $r){
  $q=Task::with(['assignee','subject'])->latest('due_at');
  if($r->filled('status'))$q->where('status',$r->string('status'));
  return $q->paginate(30);
 }

 public function storeTask(Request $r){
  $d=$r->validate([
   'title'=>'required|max:190',
   'description'=>'nullable',
   'priority'=>'nullable|max:20',
   'due_at'=>'nullable|date',
   'assigned_to'=>['nullable',$this->existsInWorkspace($r,'users')],
   'subject_type'=>'nullable|in:lead,contact,customer',
   'subject_id'=>'nullable|integer',
  ]);
  if(isset($d['subject_type'])){
   $map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];
   $model=$map[$d['subject_type']]::where('workspace_id',$this->workspace($r))->findOrFail($d['subject_id']);
   $d['subject_type']=$map[$d['subject_type']];
   $d['subject_id']=$model->id;
  } elseif(isset($d['subject_id'])) {
   abort(422,'subject_type is required when subject_id is supplied.');
  }
  $t=Task::create($d+['workspace_id'=>$this->workspace($r),'status'=>'pending']);
  $this->audit($r,'task.created','task',$t->id,$t->toArray());
  return response()->json($t->load('assignee','subject'),201);
 }

 public function updateTask(Request $r,Task $task){
  abort_unless((int)$task->workspace_id===(int)$this->workspace($r),404);
  $d=$r->validate([
   'title'=>'nullable|max:190','description'=>'nullable','priority'=>'nullable|max:20','status'=>'nullable|max:30','due_at'=>'nullable|date',
   'assigned_to'=>['nullable',$this->existsInWorkspace($r,'users')],
  ]);
  $task->update($d);
  $this->audit($r,'task.updated','task',$task->id,$d);
  return $task->refresh();
 }

 public function tags(Request $r){return Tag::orderBy('name')->get();}

 public function storeTag(Request $r){
  $d=$r->validate(['name'=>'required|max:80','color'=>'nullable|max:20']);
  $tag=Tag::create(['workspace_id'=>$this->workspace($r),'name'=>$d['name'],'color'=>$d['color']??null]);
  $this->audit($r,'tag.created','tag',$tag->id,$tag->toArray());
  return response()->json($tag,201);
 }

 public function setLeadTags(Request $r,Lead $lead){
  abort_unless((int)$lead->workspace_id===(int)$this->workspace($r),404);
  $d=$r->validate(['tag_ids'=>'array','tag_ids.*'=>$this->existsInWorkspace($r,'tags')]);
  $lead->tags()->sync($d['tag_ids']??[]);
  return ['tags'=>$lead->tags()->orderBy('name')->get()];
 }

 public function setContactTags(Request $r,Contact $contact){
  abort_unless((int)$contact->workspace_id===(int)$this->workspace($r),404);
  $d=$r->validate(['tag_ids'=>'array','tag_ids.*'=>$this->existsInWorkspace($r,'tags')]);
  $contact->tags()->sync($d['tag_ids']??[]);
  return ['tags'=>$contact->tags()->orderBy('name')->get()];
 }

 public function orders(Request $r){return Order::with(['customer.contact','items','payments'])->latest()->paginate(20);}

 public function storeOrder(Request $r){
  $d=$r->validate([
   'customer_id'=>['nullable',$this->existsInWorkspace($r,'customers')],
   'order_number'=>'nullable|max:80',
   'status'=>'nullable|max:30',
   'tax'=>'nullable|numeric',
   'order_date'=>'nullable|date',
   'notes'=>'nullable',
   'items'=>'required|array|min:1',
   'items.*.product_id'=>['nullable',$this->existsInWorkspace($r,'products')],
   'items.*.description'=>'required|max:190',
   'items.*.qty'=>'required|numeric|min:0.01',
   'items.*.unit_price'=>'required|numeric|min:0',
  ]);
  $total=0;
  DB::beginTransaction();
  try{
   $order=Order::create([
    'workspace_id'=>$this->workspace($r),
    'customer_id'=>$d['customer_id']??null,
    'order_number'=>$d['order_number']??('ORD-'.now()->format('YmdHis')),
    'status'=>$d['status']??'pending',
    'tax'=>$d['tax']??0,
    'order_date'=>$d['order_date']??now()->toDateString(),
    'notes'=>$d['notes']??null,
   ]);
   foreach($d['items'] as $item){
    $line=$item['qty']*$item['unit_price']; $total+=$line;
    OrderItem::create($item+['order_id'=>$order->id,'line_total'=>$line]);
   }
   $order->update(['subtotal'=>$total,'total'=>$total+(float)$order->tax]);
   DB::commit();
   $this->audit($r,'order.created','order',$order->id,$order->toArray());
   return response()->json($order->load('items'),201);
  }catch(\Throwable $e){DB::rollBack();throw $e;}
 }

 public function payments(Request $r){return Payment::with(['customer.contact','order'])->latest('paid_at')->paginate(30);}

 public function storePayment(Request $r){
  $d=$r->validate([
   'customer_id'=>['nullable',$this->existsInWorkspace($r,'customers')],
   'order_id'=>['nullable',$this->existsInWorkspace($r,'orders')],
   'amount'=>'required|numeric|min:0.01',
   'method'=>'required|max:30',
   'reference'=>'nullable|max:190',
   'status'=>'nullable|max:30',
   'paid_at'=>'nullable|date',
   'notes'=>'nullable',
  ]);
  $p=Payment::create($d+['workspace_id'=>$this->workspace($r),'status'=>$d['status']??'received','paid_at'=>$d['paid_at']??now()]);
  $this->audit($r,'payment.created','payment',$p->id,$p->toArray());
  return response()->json($p,201);
 }

 public function importLeads(Request $r){
  $data=$r->validate(['rows'=>'required|array|max:5000']);
  $created=0;
  foreach($data['rows'] as $row){
   if(empty($row['first_name'])||empty($row['mobile']))continue;
   $assigned=$row['assigned_to']??null;
   if($assigned && !\App\Models\User::where('workspace_id',$this->workspace($r))->whereKey($assigned)->exists())$assigned=null;
   Lead::create([
    'workspace_id'=>$this->workspace($r),
    'first_name'=>$row['first_name'],
    'last_name'=>$row['last_name']??null,
    'company'=>$row['company']??null,
    'email'=>$row['email']??null,
    'mobile'=>$row['mobile'],
    'whatsapp'=>$row['whatsapp']??$row['mobile'],
    'source'=>$row['source']??'import',
    'status'=>$row['status']??'new',
    'pipeline_stage'=>$row['pipeline_stage']??'new',
    'requirement'=>$row['requirement']??null,
    'notes'=>$row['notes']??null,
    'assigned_to'=>$this->user($r)->role==='sales'?$this->user($r)->id:$assigned,
    'created_by'=>$this->user($r)->id,
   ]);
   $created++;
  }
  $this->audit($r,'lead.import','lead',null,['created'=>$created]);
  return ['created'=>$created];
 }

 public function products(Request $r){return Product::where('active',true)->orderBy('name')->get();}

 public function quotations(Request $r){return Quotation::with(['customer.contact','items.product'])->latest()->paginate(20);}

 public function storeQuotation(Request $r){
  $d=$r->validate([
   'customer_id'=>['nullable',$this->existsInWorkspace($r,'customers')],
   'quote_number'=>'nullable|max:80',
   'status'=>'nullable|max:30',
   'tax'=>'nullable|numeric',
   'valid_until'=>'nullable|date',
   'notes'=>'nullable',
   'items'=>'required|array|min:1',
   'items.*.product_id'=>['nullable',$this->existsInWorkspace($r,'products')],
   'items.*.description'=>'required|max:190',
   'items.*.qty'=>'required|numeric|min:0.01',
   'items.*.unit_price'=>'required|numeric|min:0',
  ]);
  $subtotal=0;
  DB::beginTransaction();
  try{
   $q=Quotation::create([
    'workspace_id'=>$this->workspace($r),
    'customer_id'=>$d['customer_id']??null,
    'quote_number'=>$d['quote_number']??('QTN-'.now()->format('YmdHis')),
    'status'=>$d['status']??'draft',
    'tax'=>$d['tax']??0,
    'valid_until'=>$d['valid_until']??null,
    'notes'=>$d['notes']??null,
   ]);
   foreach($d['items'] as $item){
    $line=$item['qty']*$item['unit_price']; $subtotal+=$line;
    QuotationItem::create($item+['quotation_id'=>$q->id,'line_total'=>$line]);
   }
   $q->update(['subtotal'=>$subtotal,'total'=>$subtotal+(float)$q->tax]);
   DB::commit();
   $this->audit($r,'quotation.created','quotation',$q->id,$q->toArray());
   return response()->json($q->load('items'),201);
  }catch(\Throwable $e){DB::rollBack();throw $e;}
 }

 public function uploadRecording(Request $r,Call $call){
  abort_unless((int)$call->workspace_id===(int)$this->workspace($r),404);
  $r->validate(['recording'=>'required|file|max:51200|mimes:m4a,mp3,wav,3gp,amr,ogg']);
  $file=$r->file('recording');
  $path=$file->store('call-recordings/'.now()->format('Y/m'),'local');
  $call->update(['recording_path'=>$path,'recording_name'=>$file->getClientOriginalName(),'recording_size'=>$file->getSize()]);
  $this->audit($r,'call.recording_uploaded','call',$call->id,['size'=>$file->getSize()]);
  return response()->json(['message'=>'Recording uploaded','call'=>$call]);
 }
}
