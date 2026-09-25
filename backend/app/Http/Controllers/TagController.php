<?php
namespace App\Http\Controllers;
use App\Models\Tag;
use App\Models\Lead;
use Illuminate\Http\Request;
class TagController extends Controller{
public function index(){return view('tags.index',['tags'=>Tag::latest()->get(),'leads'=>Lead::latest()->limit(100)->get()]);}
public function store(Request $r){$d=$r->validate(['name'=>'required|max:80','color'=>'nullable|max:20']);Tag::firstOrCreate(['name'=>$d['name']],['color'=>$d['color']??'#2563eb']);return back()->with('success','Tag saved.');}
public function attach(Request $r,Lead $lead){$d=$r->validate(['tag_id'=>'required|exists:tags,id']);$lead->tags()->syncWithoutDetaching([$d['tag_id']]);return back()->with('success','Tag attached.');}
}