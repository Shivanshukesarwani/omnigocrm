<?php
namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Lead;

class DashboardController extends Controller {
    public function __invoke(): View { return view('dashboard.index',['leadCount'=>Lead::count()]); }
}
