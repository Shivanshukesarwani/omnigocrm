<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadApiController extends Controller {
    public function index() { return Lead::latest()->paginate(25); }
    public function store(Request $request) {
        $data=$request->validate(['first_name'=>'required|string|max:100','last_name'=>'nullable|string|max:100','phone'=>'required|string|max:30','email'=>'nullable|email|max:190','company_name'=>'nullable|string|max:190','source'=>'nullable|string|max:100']);
        return Lead::create($data+['status'=>'new']);
    }
    public function show(Lead $lead) { return $lead; }
    public function update(Request $request, Lead $lead) { $lead->update($request->only(['first_name','last_name','phone','email','company_name','source','status','notes','assigned_to'])); return $lead->refresh(); }
    public function destroy(Lead $lead) { $lead->delete(); return response()->noContent(); }
}
