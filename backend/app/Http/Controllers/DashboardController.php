<?php
namespace App\Http\Controllers;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\FollowUp;
class DashboardController extends Controller {
 public function __invoke(){return view('dashboard.index',['leadCount'=>Lead::count(),'contactCount'=>Contact::count(),'customerCount'=>Customer::count(),'pendingFollowUps'=>FollowUp::where('status','pending')->whereDate('scheduled_for','<=',now()->endOfDay())->count()]);}
}
