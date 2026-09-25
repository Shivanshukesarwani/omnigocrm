<?php
namespace App\Support;
use App\Models\AuditLog;
class Audit{
public static function record($subject,string $action,?string $description=null,array $meta=[]):void{
$u=request()->attributes->get('crmUser')??request()->attributes->get('apiUser');$w=app(WorkspaceContext::class)->id();if(!$w)return;
AuditLog::create(['workspace_id'=>$w,'user_id'=>$u?->id,'action'=>$action,'entity_type'=>$subject::class,'entity_id'=>$subject->getKey(),'after'=>array_merge(['description'=>$description],$meta),'ip'=>request()->ip()]);
}}
