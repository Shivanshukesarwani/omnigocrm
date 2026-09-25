@extends('layouts.app')
@section('content')
<h1>WhatsApp Templates</h1><form method="post" action="{{ route('templates.store') }}">@csrf<input name="name" placeholder="Template name"><input name="situation" placeholder="Situation e.g. follow_up"><textarea name="body" placeholder="Hello {first_name}..."></textarea><button>Save</button></form><hr>@foreach($templates as $t)<div><b>{{ $t->name }}</b> <span>{{ $t->situation }}</span><p>{{ $t->body }}</p></div>@endforeach
@endsection
