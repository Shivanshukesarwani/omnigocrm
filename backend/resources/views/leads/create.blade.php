@extends('layouts.app')
@section('content')
<h1>Add Lead</h1>
<form method="post" action="{{ route('leads.store') }}">@csrf
@foreach(['first_name'=>'First name','last_name'=>'Last name','company'=>'Company','email'=>'Email','mobile'=>'Mobile','whatsapp'=>'WhatsApp','source'=>'Lead source','requirement'=>'Requirement','notes'=>'Notes'] as $name=>$label)<label>{{ $label }}<input class="input" name="{{ $name }}" value="{{ old($name) }}"></label>@endforeach
<label>Status<select class="input" name="status"><option value="new">New</option><option value="contacted">Contacted</option><option value="qualified">Qualified</option><option value="proposal">Proposal</option></select></label>
<label>Assign to<select class="input" name="assigned_to"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label>
<button class="btn" type="submit">Create lead</button>
</form>
@endsection
