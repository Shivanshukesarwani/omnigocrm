@extends('layouts.app')
@section('content')
<h1>{{ $customer->customer_code }}</h1>
<p>{{ $customer->contact->first_name }} {{ $customer->contact->last_name }}</p>
<p>{{ $customer->contact->company }} · {{ $customer->contact->mobile }}</p>
<h2>Quotations</h2>
@forelse($customer->quotations as $q)<p>{{ $q->quote_number }} · {{ $q->status }} · {{ $q->total }}</p>@empty<p>No quotations yet.</p>@endforelse
@endsection
