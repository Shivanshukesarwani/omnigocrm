<?php
namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller {
    public function index(): View { return view('leads.index',['leads'=>Lead::latest()->paginate(25)]); }
    public function create(): View { return view('leads.create'); }
    public function store(Request $request): RedirectResponse {
        $data=$request->validate([
            'first_name'=>'required|string|max:100',
            'last_name'=>'nullable|string|max:100',
            'phone'=>'required|string|max:30',
            'email'=>'nullable|email|max:190',
            'company_name'=>'nullable|string|max:190',
            'source'=>'nullable|string|max:100'
        ]);
        Lead::create($data+['status'=>'new']);
        return redirect()->route('leads.index')->with('success','Lead created.');
    }
    public function show(Lead $lead): View { return view('leads.show',compact('lead')); }
}
