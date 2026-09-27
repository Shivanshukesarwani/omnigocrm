<?php
namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;
class CompanyController extends Controller{
public function index(){return view('companies.index',['companies'=>Company::latest()->paginate(25)]);}
public function store(Request $r){$d=$r->validate(['name'=>'required|max:180','email'=>'nullable|email','phone'=>'nullable|max:30','website'=>'nullable|url','address'=>'nullable']);Company::create($d);return back()->with('success','Company saved.');}
}