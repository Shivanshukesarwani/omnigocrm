<?php
namespace App\Support;
use App\Models\Workspace;
class WorkspaceContext{
private ?Workspace $workspace=null;
public function set(?Workspace $workspace):void{$this->workspace=$workspace;}
public function get():?Workspace{return $this->workspace;}
public function id():?int{return $this->workspace?->id;}
}