@extends('layouts.app')
@section('title','· {{ $order->order_number }}')
@section('content')
<div class="page-head"><div><h1>{{ $order->order_number }}</h1><p class="muted">{{ $order->customer?->contact?->first_name }} {{ $order->customer?->contact?->last_name }}</p></div><a class="btn" href="{{ route('orders.index') }}">Back</a></div>
<section class="panel"><table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Total</th></tr></thead><tbody>@foreach($items as $i)<tr><td>{{ $i->description }}</td><td>{{ $i->qty }}</td><td>₹{{ number_format($i->unit_price,2) }}</td><td>₹{{ number_format($i->line_total,2) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3">Subtotal</th><th>₹{{ number_format($order->subtotal,2) }}</th></tr><tr><th colspan="3">Tax</th><th>₹{{ number_format($order->tax,2) }}</th></tr><tr><th colspan="3">Total</th><th>₹{{ number_format($order->total,2) }}</th></tr></tfoot></table></section>
@endsection