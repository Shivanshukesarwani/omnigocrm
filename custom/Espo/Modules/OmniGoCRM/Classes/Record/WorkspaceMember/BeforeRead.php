<?php
namespace Espo\Modules\OmniGoCRM\Classes\Record\WorkspaceMember;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\ReadHook;
use Espo\Core\Record\ReadParams;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;
class BeforeRead implements ReadHook {
 public function __construct(private User $user, private Config $config, private EntityManager $entityManager) {}
 public function process(Entity $entity, ReadParams $params): void {
  if ($this->user->isSystem()) return;
  if ((bool)$this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;
  $workspaceId=trim((string)$entity->get('workspaceId')); $current=trim((string)$this->user->get('omniGoCRMCurrentWorkspaceId'));
  if ($workspaceId==='' || $workspaceId!==$current) throw new Forbidden('This workspace membership is outside the active workspace.');
  $membership=$this->entityManager->getRDBRepository('WorkspaceMember')->where(['workspaceId'=>$workspaceId,'userId'=>$this->user->getId(),'status'=>'Active','deleted'=>false])->findOne();
  if (!$membership) throw new Forbidden('You are not an active member of this workspace.');
  $workspace=$this->entityManager->getEntityById('Workspace',$workspaceId);
  if (!$workspace || $workspace->get('status')!=='Active') throw new Forbidden('This workspace is not active.');
 }
}
