<?php
namespace App\Http\Controllers;
use App\Models\Lead;
use Illuminate\Http\Request;
class ImportController{
public function show(){return view('imports.leads');}
public function leads(Request $r){
$r->validate(['csv'=>'required|file|mimes:csv,txt|max:10240']);
$file=$r->file('csv');$h=fopen($file->getRealPath(),'r');$header=array_map('trim',fgetcsv($h)?:[]);$allowed=['first_name','last_name','company','email','mobile','whatsapp','source','status','pipeline_stage','requirement','notes'];$count=0;
while(($row=fgetcsv($h))!==false){$row=array_pad($row,count($header),null);$raw=array_combine($header,$row);$d=[];foreach($allowed as $k)if(array_key_exists($k,$raw))$d[$k]=trim((string)$raw[$k]);if(empty($d['first_name'])||empty($d['mobile']))continue;$d['status']=$d['status']??'new';$d['pipeline_stage']=$d['pipeline_stage']??'new';$d['created_by']=$r->attributes->get('crmUser')->id;Lead::create($d);$count++;}
fclose($h);return back()->with('success',$count.' leads imported.');
}}
