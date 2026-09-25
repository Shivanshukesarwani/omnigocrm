<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
class ProductController extends Controller{
public function index(){return view('products.index',['products'=>Product::latest()->paginate(30)]);}
public function store(Request $r){$d=$r->validate(['name'=>'required|max:180','sku'=>'nullable|max:80','description'=>'nullable','price'=>'required|numeric|min:0','unit'=>'required|max:30']);Product::create($d+['active'=>true]);return back()->with('success','Product/service saved.');}
public function archive(Product $product){$product->update(['active'=>false]);return back()->with('success','Product/service archived.');}
}