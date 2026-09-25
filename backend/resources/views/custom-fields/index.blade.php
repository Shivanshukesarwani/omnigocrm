@extends('layouts.app')
@section('title','· Custom Fields')
@section('content')
<div class="page-head"><div><h1>Custom Fields</h1><p class="muted">Workspace-specific fields for leads, contacts and customers.</p></div></div>
<section class="panel"><h2>New field</h2>
<form method="post" action="{{ route('custom-fields.store') }}">@csrf
<div class="form-grid">
<div><label>Entity<select name="entity_type"><option value="lead">Lead</option><option value="contact">Contact</option><option value="customer">Customer</option></select></label></div>
<div><label>Field name<input name="name" required></label></div>
<div><label>Field key<input name="key" pattern="[a-z][a-z0-9_]*" required></label></div>
<div><label>Type<select name="type"><option value="text">Text</option><option value="number">Number</option><option value="date">Date</option><option value="select">Select</option><option value="boolean">Yes / No</option></select></label></div>
</div>
<button class="btn primary">Save field</button></form></section>
<section class="panel"><h2>Fields</h2>
<table><thead><tr><th>Entity</th><th>Key</th><th>Name</th><th>Type</th></tr></thead><tbody>
@foreach($fields as $f)<tr><td>{{ $f->entity_type }}</td><td>{{ $f->key }}</td><td>{{ $f->name }}</td><td>{{ $f->type }}</td></tr>@endforeach
</tbody></table></section>
@endsection
