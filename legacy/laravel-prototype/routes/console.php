<?php
use Illuminate\Support\Facades\Schedule;
use App\Models\FollowUp;
use App\Notifications\FollowUpDueNotification;

Schedule::call(function(){
    FollowUp::where('status','pending')->where('scheduled_for','<',now())->each(function(FollowUp $f){
        $f->update(['status'=>'overdue']);
        if($f->assignee && !$f->assignee->notifications()->where('data->follow_up_id',$f->id)->exists())$f->assignee->notify(new FollowUpDueNotification($f));
    });
})->hourly();
