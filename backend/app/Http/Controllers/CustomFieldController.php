<?php
namespace AppHttpControllers;
use AppModelsCustomField;
use IlluminateHttpRequest;
class CustomFieldController extends Controller{
public function index(){return view('custom-fields.index',['fields'=>CustomField::latest()->get()]);}
public function store(Request $r){$d=$r->validate(['entity'=>'required|in:lead,contact,customer','field_key'=>'required|max:80','label'=>'required|max:120','field_type'=>'required|in:text,number,date,select,boolean','options'=>'nullable','required'=>'nullable|boolean']);CustomField::create($d);return back()->with('success','Custom field saved.');}
}