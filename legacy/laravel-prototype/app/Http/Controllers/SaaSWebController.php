<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Task;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\AuditLog;

class SaaSWebController extends Controller {
 public function operations(){return view('operations.index',['companies'=>Company::count(),'tasks'=>Task::where('status','pending')->count(),'products'=>Product::count(),'quotations'=>Quotation::count(),'orders'=>Order::count(),'payments'=>Payment::sum('amount')]);}
 public function audit(){return view('operations.audit',['logs'=>AuditLog::with('user')->latest()->paginate(50)]);}
}
