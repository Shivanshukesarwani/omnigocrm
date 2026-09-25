<?php
namespace App\Http\Controllers;
use App\Models\Lead;
use Illuminate\Http\Request;
class PipelineController extends Controller{
public function index(){return view('pipeline.index',['groups'=>collect(['new','contacted','qualified','proposal','negotiation','won','lost'])->mapWithKeys(fn($s)=>[$s=>Lead::where('pipeline_stage',$s)->latest()->get()])]);}
public function move(Request $r,Lead $lead){$d=$r->validate(['pipeline_stage'=>'required|in:new,contacted,qualified,proposal,negotiation,won,lost']);$lead->update(['pipeline_stage'=>$d['pipeline_stage'],'status'=>$d['pipeline_stage']]);return back()->with('success','Lead stage updated.');}
}