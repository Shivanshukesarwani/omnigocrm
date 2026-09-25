@extends('layouts.app')
@section('title','· {{ $quotation->quote_number }}')
@section('content')
<div class="page-head"><div><h1>{{ $quotation->quote_number }}</h1><p class="muted">{{ $quotation->customer?->contact?->first_name }} {{ $quotation->customer?->contact?->last_name }}</p></div><a class="btn" href="{{ route('quotations.index') }}">Back</a></div>
<section class="panel"><table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Total</th></tr></thead><tbody>@foreach($quotation->items as $i)<tr><td>{{ $i->description }}</td><td>{{ $i->qty }}</td><td>₹{{ number_format($i->unit_price,2) }}</td><td>₹{{ number_format($i->line_total,2) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3">Subtotal</th><th>₹{{ number_format($quotation->subtotal,2) }}</th></tr><tr><th colspan="3">Tax</th><th>₹{{ number_format($quotation->tax,2) }}</th></tr><tr><th colspan="3">Total</th><th>₹{{ number_format($quotation->total,2) }}</th></tr></tfoot></table></section>
@endsection