<?php
namespace App\Http\Controllers;
use App\Models\Task;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Http\Request;
class TaskController extends Controller{
public function index(){return view('tasks.index',['tasks'=>Task::with('assignee')->latest('due_at')->paginate(30),'users'=>User::orderBy('name')->get(),'leads'=>Lead::latest()->limit(100)->get()]);}
public function store(Request $r){$d=$r->validate(['title'=>'required|max:180','description'=>'nullable','due_at'=>'nullable|date','priority'=>'required|in:low,normal,high,urgent','assigned_to'=>'nullable|exists:users,id','subject_id'=>'nullable|integer']);if(isset($d['subject_id']))$d['subject_type']=Lead::class;Task::create($d+['status'=>'open']);return back()->with('success','Task created.');}
public function complete(Task $task){$task->update(['status'=>'completed']);return back()->with('success','Task completed.');}
}
