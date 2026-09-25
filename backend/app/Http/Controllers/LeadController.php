<?php
namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
 public function index(Request $request){
  $q=Lead::with('assignee')->latest();
  if($request->filled('search')){
   $s=$request->search;
   $q->where(fn($x)=>$x->where('first_name','like',"%$s%")->orWhere('last_name','like',"%$s%")->orWhere('mobile','like',"%$s%")->orWhere('company','like',"%$s%"));
  }
  return view('leads.index',['leads'=>$q->paginate(20)->withQueryString()]);
 }

 public function create(){return view('leads.create',['users'=>User::orderBy('name')->get()]);}

 public function store(Request $request){
  $workspace=$request->attributes->get('workspace');
  $d=$request->validate([
   'first_name'=>'required|max:100','last_name'=>'nullable|max:100','company'=>'nullable|max:150',
   'email'=>'nullable|email','mobile'=>'required|max:30','whatsapp'=>'nullable|max:30',
   'source'=>'nullable|max:100','status'=>'nullable|max:40','pipeline_stage'=>'nullable|max:40',
   'requirement'=>'nullable','notes'=>'nullable',
   'assigned_to'=>['nullable',Rule::exists('users','id')->where(fn($q)=>$q->where('workspace_id',$workspace->id))],
  ]);
  $d['created_by']=$request->attributes->get('crmUser')->id;
  $d['status']=$d['status']??'new';
  $d['pipeline_stage']=$d['pipeline_stage']??'new';
  $lead=Lead::create($d);
  Audit::record($lead,'lead.created','Lead created');
  return redirect()->route('leads.show',$lead)->with('success','Lead created.');
 }

 public function show(Lead $lead){
  $lead->load(['assignee','followUps'=>fn($q)=>$q->orderBy('scheduled_for'),'calls'=>fn($q)=>$q->latest('called_at')]);
  return view('leads.show',['lead'=>$lead,'templates'=>MessageTemplate::where('active',true)->orderBy('situation')->get(),'users'=>User::orderBy('name')->get()]);
 }

 public function convert(Request $request,Lead $lead){
  if($lead->converted_contact_id)return back()->with('error','This lead has already been converted.');
  $contact=Contact::create([
   'workspace_id'=>$lead->workspace_id,'first_name'=>$lead->first_name,'last_name'=>$lead->last_name,
   'company'=>$lead->company,'email'=>$lead->email,'mobile'=>$lead->mobile,'whatsapp'=>$lead->whatsapp,
   'notes'=>$lead->notes,'source_lead_id'=>$lead->id,'company_id'=>$lead->company_id
  ]);
  $lead->update(['converted_contact_id'=>$contact->id,'status'=>'converted','pipeline_stage'=>'converted']);
  Audit::record($lead,'lead.converted','Lead converted to contact',['contact_id'=>$contact->id]);
  return redirect()->route('contacts.show',$contact)->with('success','Lead converted to contact.');
 }
}
