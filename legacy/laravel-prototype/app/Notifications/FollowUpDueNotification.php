<?php
namespace App\Notifications;
use App\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class FollowUpDueNotification extends Notification{
use Queueable;
public function __construct(public FollowUp $followUp){}
public function via(object $notifiable):array{return ['database'];}
public function toDatabase(object $notifiable):array{return ['title'=>'Follow-up due','message'=>'A follow-up is due for '.class_basename($this->followUp->subject_type).' #'.$this->followUp->subject_id,'follow_up_id'=>$this->followUp->id,'scheduled_for'=>$this->followUp->scheduled_for?->toISOString()];}
}