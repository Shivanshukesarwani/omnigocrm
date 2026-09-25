<?php
namespace App\Http\Controllers;
use App\Models\Customer;
class CustomerController extends Controller {
 public function index(){return view('customers.index',['customers'=>Customer::with('contact')->latest()->paginate(20)]);}
 public function show(Customer $customer){$customer->load(['contact','quotations']);return view('customers.show',['customer'=>$customer]);}
}
