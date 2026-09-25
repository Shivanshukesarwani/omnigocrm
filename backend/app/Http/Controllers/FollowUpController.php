<?php
namespace App\Http\Controllers;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
class FollowUpController extends Controller{
public function index(){return view('followups.index',['followups'=>FollowUp::with(['subject','assignee'])->latest('scheduled_for')->paginate(30),'users'=>User::orderBy('name')->get(),'leads'=>Lead::latest()->limit(100)->get(),'contacts'=>Contact::latest()->limit(100)->get(),'customers'=>Customer::latest()->limit(100)->get()]);}
public function store(Request $r){$d=$r->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','scheduled_for'=>'required|date','type'=>'required|max:40','assigned_to'=>'nullable|exists:users,id','note'=>'nullable']);$map=['lead'=>Lead::class,'contact'=>Contact::class,'customer'=>Customer::class];$d['subject_type']=$map[$d['subject_type']];FollowUp::create($d+['status'=>'pending']);return back()->with('success','Follow-up scheduled.');}
public function complete(FollowUp $followUp){$followUp->update(['status'=>'completed']);return back()->with('success','Follow-up completed.');}
}