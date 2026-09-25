<?php
namespace App\Http\Controllers;
use App\Models\Call;
use Illuminate\Support\Facades\Storage;
class CallController extends Controller
{
    public function store(\Illuminate\Http\Request $request){
        $data=$request->validate(['subject_type'=>'required|in:lead,contact,customer','subject_id'=>'required|integer','phone'=>'required|max:30','duration_seconds'=>'nullable|integer|min:0','status'=>'nullable|max:30','direction'=>'nullable|max:20','called_at'=>'nullable|date']);
        $map=['lead'=>\App\Models\Lead::class,'contact'=>\App\Models\Contact::class,'customer'=>\App\Models\Customer::class];
        $data['subject_type']=$map[$data['subject_type']]; $data['user_id']=$request->attributes->get('crmUser')->id; $data['called_at']=$data['called_at']??now();
        unset($data['subject_id']);
        $call=Call::create(array_merge($data,['subject_id'=>$request->input('subject_id')]));
        return back()->with('success','Call logged.');
    }
    public function recording(Call $call){
        abort_unless($call->recording_path,404);
        $stream=Storage::disk('local')->readStream($call->recording_path); $mime=Storage::disk('local')->mimeType($call->recording_path) ?: 'audio/*'; $name=$call->recording_name ?: 'call-recording';
        return response()->stream(function()use($stream){fpassthru($stream);if(is_resource($stream))fclose($stream);},200,['Content-Type'=>$mime,'Content-Disposition'=>'inline; filename="'.addslashes($name).'"']);
    }
}
