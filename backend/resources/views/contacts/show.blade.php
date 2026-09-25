@extends('layouts.app')
@section('content')
<h1>{{ $contact->first_name }} {{ $contact->last_name }}</h1><p>{{ $contact->company }} · {{ $contact->designation }}</p><p>{{ $contact->mobile }} · {{ $contact->email }}</p><p><a href="tel:{{ $contact->mobile }}">Call</a> | <a href="https://wa.me/{{ preg_replace('/\D+/','',$contact->whatsapp ?: $contact->mobile) }}">WhatsApp</a></p>
@if($contact->customer)<p>Customer: {{ $contact->customer->customer_code }}</p>@else<form method="post" action="{{ route('contacts.convert',$contact) }}">@csrf<button>Convert to Customer</button></form>@endif
@endsection
