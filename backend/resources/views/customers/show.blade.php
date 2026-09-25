@extends('layouts.app')
@section('title','· Customer')
@section('content')
<div class="page-head"><div><h1>{{ $customer->customer_code }}</h1><p class="muted">{{ $customer->contact?->first_name }} {{ $customer->contact?->last_name }} · {{ $customer->contact?->company ?: 'Customer' }}</p></div>@if($customer->contact?->whatsapp || $customer->contact?->mobile)<a class="btn primary" target="_blank" href="https://wa.me/{{ preg_replace('/\D+/','',$customer->contact->whatsapp ?: $customer->contact->mobile) }}">WhatsApp</a>@endif</div>
<div class="cards"><div class="card"><span>Status</span><strong>{{ $customer->status }}</strong></div><div class="card"><span>Lifetime value</span><strong>₹{{ number_format((float)$customer->lifetime_value,2) }}</strong></div><div class="card"><span>Phone</span><strong>{{ $customer->contact?->mobile ?: '—' }}</strong></div></div>
<section class="panel"><h2>Customer notes</h2><p>{{ $customer->notes ?: 'No notes yet.' }}</p></section>
<section class="panel"><h2>Recent payments</h2><table><thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead><tbody>@forelse($customer->payments as $p)<tr><td>{{ $p->paid_at }}</td><td>₹{{ number_format((float)$p->amount,2) }}</td><td>{{ strtoupper($p->method) }}</td></tr>@empty<tr><td colspan="3">No payments.</td></tr>@endforelse</tbody></table></section>
@endsection