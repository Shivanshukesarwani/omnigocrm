<?php
namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
 public function index(){
  return view('custom-fields.index',['fields'=>CustomField::latest()->get()]);
 }

 public function store(Request $r){
  $workspace=$r->attributes->get('workspace');
  $d=$r->validate([
   'entity_type'=>'required|in:lead,contact,customer',
   'name'=>'required|max:120',
   'key'=>['required','max:80',Rule::regex('/^[a-z][a-z0-9_]*$/')],
   'type'=>'required|in:text,number,date,select,boolean',
   'active'=>'nullable|boolean',
  ]);
  $d['workspace_id']=$workspace->id;
  $d['active']=$d['active']??true;
  CustomField::create($d);
  return back()->with('success','Custom field saved.');
 }
}
