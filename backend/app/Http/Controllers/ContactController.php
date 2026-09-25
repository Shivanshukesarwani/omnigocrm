<?php
namespace App\Http\Controllers;
use App\Models\Contact;use App\Models\Customer;use App\Support\Audit;
class ContactController extends Controller{
public function index(){return view('contacts.index',['contacts'=>Contact::with('customer')->latest()->paginate(20)]);}
public function show(Contact $contact){$contact->load(['customer','followUps','calls'=>fn($q)=>$q->latest('called_at')]);return view('contacts.show',['contact'=>$contact]);}
public function convert(Contact $contact){if($contact->customer)return back()->with('error','Already a customer.');$customer=Customer::create(['workspace_id'=>$contact->workspace_id,'contact_id'=>$contact->id,'customer_code'=>'CUS-'.str_pad((string)$contact->id,6,'0',STR_PAD_LEFT),'status'=>'active']);Audit::record($contact,'contact.converted','Contact converted to customer',['customer_id'=>$customer->id]);return redirect()->route('customers.show',$customer)->with('success','Contact converted to customer.');}
}