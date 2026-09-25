@extends('layouts.app')
@section('content')
<h1>{{ $lead->first_name }} {{ $lead->last_name }}</h1>
<p>{{ $lead->company }} · {{ $lead->mobile }} · {{ $lead->email }}</p>
<p>{{ $lead->requirement }}</p>
<p><a href="tel:{{ $lead->mobile }}">Call</a> | <a href="https://wa.me/{{ preg_replace('/\D+/','',$lead->whatsapp ?: $lead->mobile) }}">WhatsApp</a></p>
@if(!$lead->converted_contact_id)<form method="post" action="{{ route('leads.convert',$lead) }}">@csrf<button type="submit">Convert to Contact</button></form>@else<p>Converted to contact #{{ $lead->converted_contact_id }}</p>@endif
<h2>Follow-ups</h2>@forelse($lead->followUps as $f)<p>{{ $f->scheduled_for }} · {{ $f->status }} · {{ $f->note }}</p>@empty<p>No follow-ups.</p>@endforelse
@endsection
