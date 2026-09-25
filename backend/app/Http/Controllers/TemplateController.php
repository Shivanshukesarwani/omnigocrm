<?php
namespace App\Http\Controllers;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
class TemplateController extends Controller
{
    public function index(){return view('templates.index',['templates'=>MessageTemplate::latest()->get()]);}
    public function store(Request $request){
        $data=$request->validate(['name'=>'required|max:100','situation'=>'required|max:50','body'=>'required']);
        MessageTemplate::create($data); return back()->with('success','Template saved.');
    }
}
