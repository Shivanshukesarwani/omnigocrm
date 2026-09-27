@extends('layouts.app')
@section('title','· Notifications')
@section('content')
<div class="page-head"><div><h1>Notifications</h1><p class="muted">Follow-up and CRM alerts.</p></div></div>
<section class="panel"><table><thead><tr><th>Alert</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>@forelse($notifications as $n)<tr><td><strong>{{ data_get($n->data,'title') }}</strong><br>{{ data_get($n->data,'message') }}</td><td>{{ $n->created_at }}</td><td>{{ $n->read_at?'Read':'Unread' }}</td><td>@if(!$n->read_at)<form method="post" action="{{ route('notifications.read',$n->id) }}">@csrf<button class="btn">Mark read</button></form>@endif</td></tr>@empty<tr><td colspan="4">No notifications.</td></tr>@endforelse</tbody></table>{{ $notifications->links() }}</section>
@endsection