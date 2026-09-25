@extends('layouts.app')
@section('content')
<h1>Dashboard</h1><p class="muted">Sales overview</p>
<div class="grid">
<div class="card"><div class="muted">Leads</div><h2>{{ $leadCount }}</h2><a href="{{ route('leads.index') }}">Open leads</a></div>
<div class="card"><div class="muted">Contacts</div><h2>{{ $contactCount }}</h2><a href="{{ route('contacts.index') }}">Open contacts</a></div>
<div class="card"><div class="muted">Customers</div><h2>{{ $customerCount }}</h2><a href="{{ route('customers.index') }}">Open customers</a></div>
<div class="card"><div class="muted">Due follow-ups</div><h2>{{ $pendingFollowUps }}</h2></div>
</div>
@endsection
