<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class CrmApiController extends Controller
{
 private function user(Request $r){return $r->attributes->get('apiUser');}
 private function workspace(Request $r){return $this->user($r)->workspace_id;}

 public function dashboard(Request $r){
  $u=$this->user($r); $q=Lead::query();
  if($u->role==='sales')$q->where('assigned_to',$u->id);
  return ['counts'=>[
   'leads'=>(clone $q)->count(),'contacts'=>Contact::count(),'customers'=>Customer::count(),
   'followups'=>FollowUp::where('status','pending')->count(),'calls'=>Call::count()
  ],'recent_leads'=>(clone $q)->latest()->limit(10)->get()];
 }

 public function leads(Request $r){
  $q=Lead::with('assignee')->latest();
  if($r->filled('search')){$s=$r->string('search');$q->where(fn($x)=>$x->where('first_name','like',"%$s%")->orWhere('last_name','like',"%$s%")->orWhere('mobile','like',"%$s%")->orWhere('company','like',"%$s%"));}
  if(($u=$this->user($r))->role==='sales')$q->where('assigned_to',$u->id);
  return $q->paginate(20);
 }

 public function storeLead(Request $r){
  $u=$this->user($r);
  $d=$r->validate(['first_name'=>'required|max:100','last_name'=>'nullable|max:100','company'=>'nullable|max:150','email'=>'nullable|email','mobile'=>'required|max:30','whatsapp'=>'nullable|max:30','source'=>'nullable|max:100','status'=>'nullable|max:40','pipeline_stage'=>'nullable|max:40','requirement'=>'nullable','notes'=>'nullable']);
  $d['created_by']=$u->id;$d['assigned_to']=$u->role==='sales'?$u->id:null;$d['status']=$d['status']??'new';$d['pipeline_stage']=$d['pipeline_stage']??'new';
  return response()->json(['lead'=>Lead::create($d)],201);
 }

 public function lead(Lead $lead){return ['lead'=>$lead->load(['assignee','followUps','calls']);}

 public function convertLead(Lead $lead){
  if($lead->converted_contact_id)return response()->json(['message'=>'Already converted'],422);
  $c=Contact::create(['workspace_id'=>$lead->workspace_id,'first_name'=>$lead->first_name,'last_name'=>$lead->last_name,'company'=>$lead->company,'email'=>$lead->email,'mobile'=>$lead->mobile,'whatsapp'=>$lead->whatsapp,'notes'=>$lead->notes,'source_lead_id'=>$lead->id,'company_id'=>$lead->company_id]);
  $lead->update(['converted_contact_id'=>$c->id,'status'=>'converted','pipeline_stage'=>'converted']);
  return ['lead'=>$lead,'contact'=>$c];
 }

 public function contacts(){return Contact::with('customer')->latest()->paginate(20);}
 public function contact(Contact $contact){return ['contact'=>$contact->load(['customer','followUps','calls']);}
 public function convertContact(Contact $contact){if($contact->customer)return ['customer'=>$contact->customer];$c=Customer::create(['workspace_id'=>$contact->workspace_id,'contact_id'=>$contact->id,'customer_code'=>'CUS-'.str_pad($contact->id,6,'0',STR_PAD_LEFT),'company_id'=>$contact->company_id]);return response()->json(['customer'=>$c->load('contact')],201);}
 public function customers(){return Customer::with('contact')->latest()->paginate(20);}
 public function customer(Customer $customer){return ['customer'=>$customer->load(['contact','followUps','calls','quotations','orders','payments']);}

 public function templates(Request $r){return MessageTemplate::where('active',true)->when($r->filled('situation'),fn($q)=>$q->where('situation',$r->situation))->orderBy('situation')->get();}

 public function whatsapp(string $type,int $id,Request $r){
  $entity=match($type){
   'lead'=>Lead::findOrFail($id),
   'contact'=>Contact::findOrFail($id),
   'customer'=>Customer::with('contact')->findOrFail($id),
   default=>abort(404)
  };
  if($type==='customer')$entity=$entity->contact;
  $t=MessageTemplate::where('active',true)->when($r->filled('situation'),fn($q)=>$q->where('situation',$r->situation))->first();
  abort_unless($t,404);
  $u=$this->user($r);
  $body=strtr($t->body,['{first_name}'=>$entity->first_name,'{last_name}'=>$entity->last_name??'','{company}'=>$entity->company??'','{requirement}'=>$entity->requirement??'','{salesperson}'=>$u->name,'{company_name}'=>config('app.company_name'),'{quotation_amount}'=>'','{service}'=>'','{phone}'=>$entity->mobile??'']);
  $phone=preg_replace('/\D+/','',$entity->whatsapp?:$entity->mobile?:'');
  if(str_starts_with($phone,'0'))$phone='91'.substr($phone,1);
  if(!str_starts_with($phone,'91'))$phone='91'.$phone;
  return ['url'=>'https://wa.me/'.$phone.'?text='.rawurlencode($body),'message'=>$body,'phone'=>$phone];
 }

 public function followUps(Request $r){
  $q=FollowUp::with(['subject','assignee'])->latest('scheduled_for');
  if($r->filled('status'))$q->where('status',$r->status);
  return $q->paginate(30);
 }

 public function storeFollowUp(Request $r){
  $u=$this->user($r);
  $d=$r->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','scheduled_for'=>'required|date','type'=>'nullable|max:40','note'=>'nullable']);
  $map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];
  $subject=$map[$d['subject_type']]::where('workspace_id',$this->workspace($r))->findOrFail($d['subject_id']);
  $d['subject_type']=$map[$d['subject_type']];$d['subject_id']=$subject->id;$d['assigned_to']=$u->id;
  return response()->json(FollowUp::create($d+['status'=>'pending','workspace_id'=>$this->workspace($r)]),201);
 }

 public function storeCall(Request $r){
  $d=$r->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','phone'=>'required|max:30','duration_seconds'=>'nullable|integer|min:0','direction'=>'nullable|max:20','status'=>'nullable|max:30','called_at'=>'nullable|date']);
  $map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];
  $subject=$map[$d['subject_type']]::where('workspace_id',$this->workspace($r))->findOrFail($d['subject_id']);
  $d['subject_type']=$map[$d['subject_type']];$d['subject_id']=$subject->id;$d['user_id']=$this->user($r)->id;$d['called_at']=$d['called_at']??now();
  return response()->json(Call::create($d+['workspace_id'=>$this->workspace($r)]),201);
 }
}
