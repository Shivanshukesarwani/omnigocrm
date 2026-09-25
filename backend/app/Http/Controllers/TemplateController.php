<?php
namespace App\Http\Controllers;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
class TemplateController extends Controller{
public function index(){return view('templates.index',['templates'=>MessageTemplate::orderBy('situation')->orderBy('name')->get()]);}
public function store(Request $request){$data=$request->validate(['name'=>'required|max:100','situation'=>'required|max:50','body'=>'required']);MessageTemplate::create($data+['active'=>true]);return back()->with('success','Template saved.');}
}