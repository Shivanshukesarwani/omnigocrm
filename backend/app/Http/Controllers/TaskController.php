<?php
namespace App\Http\Controllers;
use App\Models\Task;use App\Models\User;use App\Models\Lead;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
class TaskController extends Controller{
 public function index(){ $workspace=request()->attributes->get('workspace');return view('tasks.index',['tasks'=>Task::with('assignee')->latest('due_at')->paginate(30),'users'=>User::where('workspace_id',$workspace->id)->orderBy('name')->get(),'leads'=>Lead::latest()->limit(100)->get()]);}
 public function store(Request $r){$workspace=$r->attributes->get('workspace');$d=$r->validate(['title'=>'required|max:180','description'=>'nullable','due_at'=>'nullable|date','priority'=>'required|in:low,normal,high,urgent','assigned_to'=>['nullable',Rule::exists('users','id')->where(fn($q)=>$q->where('workspace_id',$workspace->id))],'subject_id'=>'nullable|integer']);if(isset($d['subject_id'])){$subject=Lead::findOrFail($d['subject_id']);$d['subject_type']=Lead::class;$d['subject_id']=$subject->id;}$d['workspace_id']=$workspace->id;Task::create($d+['status'=>'open']);return back()->with('success','Task created.');}
 public function complete(Task $task){$task->update(['status'=>'completed']);return back()->with('success','Task completed.');}
}
