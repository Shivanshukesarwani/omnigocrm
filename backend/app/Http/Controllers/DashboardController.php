<?php
namespace App\Http\Controllers;
use App\Models\Lead;use App\Models\Contact;use App\Models\Customer;use App\Models\FollowUp;use App\Models\Call;
class DashboardController extends Controller{
public function __invoke(){return view('dashboard.index',['leadCount'=>Lead::count(),'contactCount'=>Contact::count(),'customerCount'=>Customer::count(),'pendingFollowUps'=>FollowUp::where('status','pending')->where('scheduled_for','>=',now()->startOfDay())->count(),'recentLeads'=>Lead::latest()->take(8)->get(),'recentCalls'=>Call::with(['subject','user'])->latest('called_at')->take(8)->get()]);}
}