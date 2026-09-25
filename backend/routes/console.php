<?php
use Illuminate\Support\Facades\Schedule;
Schedule::call(function(){
    \App\Models\FollowUp::where('status','pending')->where('scheduled_for','<',now())->update(['status'=>'overdue']);
})->hourly();
