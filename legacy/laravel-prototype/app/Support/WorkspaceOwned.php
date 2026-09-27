<?php
namespace App\Support;
use Illuminate\Database\Eloquent\Builder;
trait WorkspaceOwned{
protected static function bootWorkspaceOwned():void{
static::creating(function($model){if(!$model->workspace_id && app(WorkspaceContext::class)->id())$model->workspace_id=app(WorkspaceContext::class)->id();});
static::addGlobalScope('workspace',function(Builder $builder){$id=app(WorkspaceContext::class)->id();if($id)$builder->where($builder->getModel()->getTable().'.workspace_id',$id);});
}
public function workspace(){return $this->belongsTo(\App\Models\Workspace::class);}
}