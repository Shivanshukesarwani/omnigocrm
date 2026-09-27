@extends('layouts.app')
@section('title','· Audit Log')
@section('content')
<div class="page-head"><div><h1>Audit Log</h1><p class="muted">Administrative trail for sensitive CRM actions.</p></div></div>
<section class="panel"><table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->user?->name ?: 'System' }}</td><td>{{ $log->action }}</td><td>{{ $log->description }}</td><td>{{ $log->ip_address ?: '—' }}</td></tr>@endforeach</tbody></table>{{ $logs->links() }}</section>
@endsection