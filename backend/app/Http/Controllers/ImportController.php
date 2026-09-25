<?php
namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
 public function show(){return view('imports.leads');}

 public function leads(Request $r){
  $r->validate(['csv'=>'required|file|max:20480|mimes:csv,txt,xlsx,xls,ods']);
  $file=$r->file('csv');
  $rows=[];

  try{
   $ext=strtolower($file->getClientOriginalExtension());
   if(in_array($ext,['xlsx','xls','ods'],true)){
    $sheet=IOFactory::load($file->getRealPath())->getActiveSheet();
    $rows=$sheet->toArray(null,true,true,true);
   }else{
    $handle=fopen($file->getRealPath(),'r');
    $header=fgetcsv($handle)?:[];
    $header=array_map(fn($v)=>trim((string)$v),$header);
    while(($row=fgetcsv($handle))!==false)$rows[]=array_combine($header,array_pad($row,count($header),null));
    fclose($handle);
   }
  }catch(\Throwable $e){
   Log::error('Lead import failed',['exception'=>$e]);
   return back()->withErrors(['csv'=>'The file could not be read.']);
  }

  if(!$rows)return back()->withErrors(['csv'=>'The uploaded file contains no data.']);

  if(isset($rows[0]) && is_array($rows[0]) && !isset($rows[0]['first_name'])){
   $headers=array_map(fn($v)=>strtolower(trim((string)$v)),array_values($rows[0]));
   $mapped=[];
   foreach(array_slice($rows,1) as $row){
    $vals=array_values($row);
    $mapped[]=array_combine($headers,array_pad($vals,count($headers),null));
   }
   $rows=$mapped;
  }

  $allowed=['first_name','last_name','company','email','mobile','whatsapp','source','status','pipeline_stage','requirement','notes'];
  $count=0;
  $workspace=$r->attributes->get('workspace');
  $user=$r->attributes->get('crmUser');

  foreach($rows as $row){
   if(!is_array($row))continue;
   $normalized=[];
   foreach($allowed as $key)if(array_key_exists($key,$row))$normalized[$key]=trim((string)$row[$key]);
   if(empty($normalized['first_name'])||empty($normalized['mobile']))continue;
   $normalized['status']=$normalized['status']??'new';
   $normalized['pipeline_stage']=$normalized['pipeline_stage']??'new';
   $normalized['created_by']=$user->id;
   $normalized['workspace_id']=$workspace->id;
   Lead::create($normalized);
   $count++;
  }

  return back()->with('success',$count.' leads imported.');
 }
}
