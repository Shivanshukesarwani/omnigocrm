<?php
namespace App\Http\Controllers;
use App\Models\Contact;
use App\Models\Customer;
class ContactController extends Controller {
 public function index(){return view('contacts.index',['contacts'=>Contact::with('customer')->latest()->paginate(25)]);}
 public function show(Contact $contact){return view('contacts.show',['contact'=>$contact->load(['customer','followUps'])]);}
 public function convert(Contact $contact){$customer=$contact->customer?:Customer::create(['contact_id'=>$contact->id,'customer_code'=>'CUS-'.str_pad((string)$contact->id,6,'0',STR_PAD_LEFT)]);return redirect()->route('customers.show',$customer)->with('success','Contact converted to customer.');}
}
