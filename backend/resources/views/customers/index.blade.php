@extends('layouts.app')
@section('content')
<h1>Customers</h1><table><tr><th>Code</th><th>Name</th><th>Company</th><th>Mobile</th></tr>@foreach($customers as $c)<tr><td><a href="{{ route('customers.show',$c) }}">{{ $c->customer_code }}</a></td><td>{{ $c->contact->first_name }} {{ $c->contact->last_name }}</td><td>{{ $c->contact->company }}</td><td>{{ $c->contact->mobile }}</td></tr>@endforeach</table>{{ $customers->links() }}
@endsection
