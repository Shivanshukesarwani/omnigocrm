<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\MessageTemplate;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class CrmApiController extends Controller
{
    private function user(Request $r){return $r->attributes->get('apiUser');}
    public function dashboard(Request $r){
        $u=$this->user($r);
        $leadQuery=Lead::query();
        if($u->role==='sales') $leadQuery->where('assigned_to',$u->id);
        return response()->json([
            'counts'=>['leads'=>(clone $leadQuery)->count(),'contacts'=>Contact::count(),'customers'=>Customer::count(),'followups'=>FollowUp::where('status','pending')->whereDate('scheduled_for','<=',now()->endOfDay())->count()],
            'recent_leads'=>(clone $leadQuery)->latest()->limit(10)->get(),
        ]);
    }
    public function leads(Request $r){
        $q=Lead::with('assignee')->latest();
        if($r->filled('search')){$s=$r->string('search')->toString();$q->where(fn($x)=>$x->where('first_name','like',"%$s%")->orWhere('last_name','like',"%$s%")->orWhere('mobile','like',"%$s%")->orWhere('company','like',"%$s%"));}
        if(($u=$this->user($r))->role==='sales') $q->where('assigned_to',$u->id);
        return response()->json($q->paginate(20));
    }
    public function storeLead(Request $r){
        $u=$this->user($r); $data=$r->validate(['first_name'=>'required|max:100','last_name'=>'nullable|max:100','company'=>'nullable|max:150','email'=>'nullable|email','mobile'=>'required|max:30','whatsapp'=>'nullable|max:30','source'=>'nullable|max:100','status'=>'nullable|max:40','requirement'=>'nullable','notes'=>'nullable','assigned_to'=>'nullable|exists:users,id']);
        $data['created_by']=$u->id; if($u->role==='sales')$data['assigned_to']=$u->id; $data['status']=$data['status']??'new';
        return response()->json(['lead'=>Lead::create($data)],201);
    }
    public function lead(Lead $lead, Request $r){$u=$this->user($r);if($u->role==='sales' && (int)$lead->assigned_to !== (int)$u->id) return response()->json(['message'=>'Forbidden'],403);$lead->load(['assignee','followUps','calls'=>fn($q)=>$q->latest('called_at')]);return response()->json(['lead'=>$lead]);}
    public function convertLead(Lead $lead, Request $r){$u=$this->user($r);if($u->role==='sales' && (int)$lead->assigned_to !== (int)$u->id) return response()->json(['message'=>'Forbidden'],403);
        if($lead->converted_contact_id) return response()->json(['message'=>'Already converted','contact_id'=>$lead->converted_contact_id],422);
        $contact=Contact::create(['first_name'=>$lead->first_name,'last_name'=>$lead->last_name,'company'=>$lead->company,'email'=>$lead->email,'mobile'=>$lead->mobile,'whatsapp'=>$lead->whatsapp,'notes'=>$lead->notes,'source_lead_id'=>$lead->id]);
        $lead->update(['converted_contact_id'=>$contact->id,'status'=>'converted']);
        return response()->json(['contact'=>$contact,'lead'=>$lead]);
    }
    public function contacts(){return response()->json(Contact::with('customer')->latest()->paginate(20));}
    public function contact(Contact $contact){return response()->json(['contact'=>$contact->load(['customer','followUps','calls'=>fn($q)=>$q->latest('called_at')])]);}
    public function convertContact(Contact $contact){
        if($contact->customer) return response()->json(['customer'=>$contact->customer]);
        $customer=Customer::create(['contact_id'=>$contact->id,'customer_code'=>'CUS-'.str_pad((string)$contact->id,6,'0',STR_PAD_LEFT)]);
        return response()->json(['customer'=>$customer->load('contact')],201);
    }
    public function customers(){return response()->json(Customer::with('contact')->latest()->paginate(20));}
    public function customer(Customer $customer){return response()->json(['customer'=>$customer->load(['contact','followUps','calls'=>fn($q)=>$q->latest('called_at'),'quotations'])]);}
    public function templates(Request $r){$q=MessageTemplate::where('active',true)->orderBy('situation')->orderBy('name');if($r->filled('situation'))$q->where('situation',$r->string('situation'));return response()->json($q->get());}
    public function whatsapp(string $type,int $id,Request $r){
        $entity=match($type){'lead'=>Lead::findOrFail($id),'contact'=>Contact::findOrFail($id),'customer'=>Customer::with('contact')->findOrFail($id),default=>abort(404)};
        if($type==='customer')$entity=$entity->contact;
        $template=MessageTemplate::where('active',true)->when($r->filled('situation'),fn($q)=>$q->where('situation',$r->string('situation')))->first();
        if(!$template)return response()->json(['message'=>'No active template found'],422);
        $sales=$this->user($r);
        $body=strtr($template->body,['{first_name}'=>$entity->first_name,'{last_name}'=>$entity->last_name??'','{company}'=>$entity->company??'','{requirement}'=>$entity->requirement??'','{salesperson}'=>$sales->name,'{company_name}'=>config('app.company_name'),'{phone}'=>$entity->mobile??'']);
        $phone=preg_replace('/\D+/','',$entity->whatsapp ?: $entity->mobile ?: ''); if(str_starts_with($phone,'0'))$phone='91'.substr($phone,1); if(!str_starts_with($phone,'91'))$phone='91'.$phone;
        $url='https://wa.me/'.$phone.'?text='.rawurlencode($body);
        return response()->json(['url'=>$url,'message'=>$body,'phone'=>$phone]);
    }
    public function followUps(Request $r){$q=FollowUp::with(['subject','assignee'])->latest('scheduled_for');if($r->filled('status'))$q->where('status',$r->string('status'));return response()->json($q->paginate(30));}
    public function storeFollowUp(Request $r){$u=$this->user($r);$data=$r->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','scheduled_for'=>'required|date','type'=>'nullable|max:40','note'=>'nullable']);$map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];$data['subject_type']=$map[$data['subject_type']];$data['assigned_to']=$u->id;$f=FollowUp::create($data+['status'=>'pending']);return response()->json($f->load('subject'),201);}
    public function storeCall(Request $r){$u=$this->user($r);$data=$r->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','phone'=>'required|max:30','duration_seconds'=>'nullable|integer|min:0','status'=>'nullable|max:30','direction'=>'nullable|max:20','called_at'=>'nullable|date']);$map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];$data['subject_type']=$map[$data['subject_type']];$data['user_id']=$u->id;$data['called_at']=$data['called_at']??now();return response()->json(Call::create($data),201);}
    public function uploadRecording(Request $r, Call $call){
        $u=$this->user($r); // Uploading is allowed to the authenticated caller; playback remains administrator-only on the web app.
        $r->validate(['recording'=>'required|file|max:51200|mimes:m4a,mp3,wav,3gp,amr,ogg']);
        $file=$r->file('recording'); $path=$file->store('call-recordings/'.now()->format('Y/m'),'local');
        $call->update(['recording_path'=>$path,'recording_name'=>$file->getClientOriginalName(),'recording_size'=>$file->getSize()]);
        return response()->json(['message'=>'Recording uploaded','call'=>$call]);
    }
}
