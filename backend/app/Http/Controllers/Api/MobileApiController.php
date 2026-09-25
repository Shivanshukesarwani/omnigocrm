<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Task;
use Illuminate\Http\Request;
class MobileApiController extends Controller{
public function companies(){return Company::latest()->paginate(30);}
public function tasks(Request $r){$q=Task::with('assignee')->latest('due_at');if($r->filled('status'))$q->where('status',$r->status);return $q->paginate(30);}
public function completeTask(Task $task){$task->update(['status'=>'completed']);return ['task'=>$task];}
public function storeCompany(Request $r){$d=$r->validate(['name'=>'required|max:180','email'=>'nullable|email','phone'=>'nullable|max:30','website'=>'nullable|url','address'=>'nullable']);return response()->json(Company::create($d),201);}
public function storeTask(Request $r){$d=$r->validate(['title'=>'required|max:180','description'=>'nullable','due_at'=>'nullable|date','priority'=>'required|in:low,normal,high,urgent','assigned_to'=>'nullable|exists:users,id']);return response()->json(Task::create($d+['status'=>'open']),201);}
}