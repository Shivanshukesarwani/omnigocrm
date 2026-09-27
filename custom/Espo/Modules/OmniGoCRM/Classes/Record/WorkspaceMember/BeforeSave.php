<?php
namespace Espo\Modules\OmniGoCRM\Classes\Record\WorkspaceMember;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;
class BeforeSave implements SaveHook {
 public function __construct(private User $user, private Config $config, private EntityManager $entityManager) {}
 public function process(Entity $entity): void {
  if ($this->user->isSystem()) return;
  if ((bool)$this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;
  $workspaceId=trim((string)$entity->get('workspaceId')); $current=trim((string)$this->user->get('omniGoCRMCurrentWorkspaceId'));
  if ($workspaceId==='' || $workspaceId!==$current) throw new Forbidden('Workspace membership must belong to the active workspace.');
  $workspace=$this->entityManager->getEntityById('Workspace',$workspaceId);
  if (!$workspace || $workspace->get('status')!=='Active') throw new Forbidden('Workspace is not active.');
  $actor=$this->entityManager->getRDBRepository('WorkspaceMember')->where(['workspaceId'=>$workspaceId,'userId'=>$this->user->getId(),'status'=>'Active','deleted'=>false])->findOne();
  if (!$actor) throw new Forbidden('You are not an active member of this workspace.');
  $actorRole=(string)$actor->get('role');
  if ($entity->isNew()) {
   $target=trim((string)$entity->get('userId')); $role=(string)($entity->get('role')?:'Agent');
   if ($target==='') throw new Forbidden('A workspace member must reference a user.');
   if ($role==='Owner') {
    if ($target!==$this->user->getId() || (string)$workspace->get('ownerUserId')!==$this->user->getId()) throw new Forbidden('Owner membership can only be created for the workspace owner.');
   } elseif (!in_array($actorRole,['Owner','Admin'],true)) throw new Forbidden('Workspace admin access is required.');
   return;
  }
  if ((string)$entity->get('workspaceId')!==$workspaceId || trim((string)$entity->get('userId'))==='') throw new Forbidden('Workspace membership identity cannot be changed.');
  if (!in_array($actorRole,['Owner','Admin'],true)) throw new Forbidden('Workspace admin access is required.');
  if ((string)$entity->get('role')==='Owner') throw new Forbidden('Owner role changes require an explicit ownership-transfer workflow.');
  if ((string)$entity->get('userId')===$this->user->getId()) throw new Forbidden('You cannot change your own workspace membership.');
  if ((string)$entity->getFetched('role')==='Owner' && $actorRole!=='Owner') throw new Forbidden('Only the workspace owner can modify an owner membership.');
 }
}
