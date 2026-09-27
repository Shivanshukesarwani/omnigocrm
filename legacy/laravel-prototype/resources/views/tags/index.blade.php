@extends('layouts.app')
@section('title','· Tags')
@section('content')
<div class="page-head"><div><h1>Tags</h1><p class="muted">Create workspace tags and attach them to leads.</p></div></div>
<div class="two-col">
<section class="panel"><h2>New tag</h2>
<form method="post" action="{{ route('tags.store') }}">@csrf
<div class="form-grid"><div><label>Name<input name="name" required></label></div><div><label>Color<input name="color" value="#2563eb"></label></div></div>
<button class="btn primary">Save tag</button></form></section>

<section class="panel"><h2>Attach tag to lead</h2>
<form id="attachTagForm" method="post" action="@if($leads->first()){{ route('tags.attach',$leads->first()) }}@endif">@csrf
<div class="form-grid">
<div><label>Lead<select id="leadSelect">@foreach($leads as $l)<option value="{{ $l->id }}">{{ $l->first_name }} {{ $l->last_name }}</option>@endforeach</select></label></div>
<div><label>Tag<select name="tag_id">@foreach($tags as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></label></div>
</div>
<button class="btn primary" type="submit">Attach</button>
</form>
<script>
document.getElementById('leadSelect')?.addEventListener('change', function(){
 document.getElementById('attachTagForm').action = '/leads/' + this.value + '/tags';
});
</script>
</section>
</div>

<section class="panel"><h2>Tags</h2>
<table><thead><tr><th>Name</th><th>Color</th></tr></thead><tbody>
@foreach($tags as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->color }}</td></tr>@endforeach
</tbody></table>
</section>
@endsection
