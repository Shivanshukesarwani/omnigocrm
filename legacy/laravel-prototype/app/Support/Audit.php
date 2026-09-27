<?php
namespace App\Support;

use App\Models\ActivityLog;
use App\Models\AuditLog;

class Audit
{
 public static function record($subject,string $action,?string $description=null,array $meta=[]):void
 {
  $user=request()->attributes->get('crmUser')??request()->attributes->get('apiUser');
  $workspace=app(WorkspaceContext::class)->get();
  if(!$workspace)return;

  $payload=['description'=>$description]+$meta;

  AuditLog::create([
   'workspace_id'=>$workspace->id,
   'user_id'=>$user?->id,
   'action'=>$action,
   'entity_type'=>$subject::class,
   'entity_id'=>$subject->getKey(),
   'after'=>$payload,
   'ip'=>request()->ip(),
  ]);

  ActivityLog::create([
   'workspace_id'=>$workspace->id,
   'user_id'=>$user?->id,
   'subject_type'=>$subject::class,
   'subject_id'=>$subject->getKey(),
   'action'=>$action,
   'description'=>$description,
   'meta'=>$meta,
   'ip_address'=>request()->ip(),
  ]);
 }
}
