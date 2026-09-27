<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
class AuditLogController extends Controller{
public function index(){abort_unless(in_array(request()->attributes->get('crmUser')?->role,['admin','super_admin'],true),403);return view('audit.index',['logs'=>AuditLog::with('user')->latest()->paginate(50)]);}
}