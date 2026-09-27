@extends('layouts.app')
@section('title','· Contacts')
@section('content')
<div class="page-head"><div><h1>Contacts</h1><p class="muted">People you have engaged with.</p></div></div>
<div class="panel"><table><thead><tr><th>Name</th><th>Company</th><th>Mobile</th><th>Customer</th></tr></thead><tbody>
@forelse($contacts as $contact)
<tr><td><a href="{{ route('contacts.show',$contact) }}">{{ $contact->first_name }} {{ $contact->last_name }}</a></td><td>{{ $contact->company ?: '—' }}</td><td>{{ $contact->mobile }}</td><td>@if($contact->customer)<a href="{{ route('customers.show',$contact->customer) }}">{{ $contact->customer->customer_code }}</a>@else—@endif</td></tr>
@empty<tr><td colspan="4">No contacts yet.</td></tr>@endforelse
</tbody></table>{{ $contacts->links() }}</div>
@endsection