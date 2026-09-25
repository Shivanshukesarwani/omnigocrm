<?php
namespace App\Support;
use App\Models\ActivityLog;
class Audit{
public static function record($subject,string $action,?string $description=null,array $meta=[]):void{
$u=request()->attributes->get('crmUser')??request()->attributes->get('apiUser');$w=app(WorkspaceContext::class)->id();if(!$w)return;
ActivityLog::create(['workspace_id'=>$w,'user_id'=>$u?->id,'subject_type'=>$subject::class,'subject_id'=>$subject->getKey(),'action'=>$action,'description'=>$description,'meta'=>$meta,'ip_address'=>request()->ip()]);
}}
