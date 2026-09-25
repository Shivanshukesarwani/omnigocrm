@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center"><h1>Leads</h1><a class="btn" href="{{ route('leads.create') }}">Add lead</a></div>
<form method="get"><input class="input" name="search" placeholder="Search name, mobile or company" value="{{ request('search') }}"></form>
<div class="tablewrap" style="margin-top:15px"><table><tr><th>Name</th><th>Company</th><th>Mobile</th><th>Status</th><th>Owner</th></tr>@foreach($leads as $lead)<tr><td><a href="{{ route('leads.show',$lead) }}">{{ $lead->first_name }} {{ $lead->last_name }}</a></td><td>{{ $lead->company }}</td><td>{{ $lead->mobile }}</td><td>{{ $lead->status }}</td><td>{{ optional($lead->assignee)->name }}</td></tr>@endforeach</table></div>
<div style="margin-top:15px">{{ $leads->links() }}</div>
@endsection
