@extends('layouts.app')
@section('title','· Follow-ups')
@section('content')
<div class="page-head"><div><h1>Follow-ups</h1><p class="muted">Schedule the next customer touchpoint.</p></div></div>
<div class="two-col"><section class="panel"><h2>Schedule follow-up</h2><form method="post" action="{{ route('followups.store') }}">@csrf<div class="form-grid"><div><label>Entity<select name="subject_type" id="type" onchange="loadSubjects()"><option value="lead">Lead</option><option value="contact">Contact</option><option value="customer">Customer</option></select></label></div><div><label>Record<select name="subject_id" id="subject"></select></label></div><div><label>Date & time<input type="datetime-local" name="scheduled_for" required></label></div><div><label>Type<select name="type"><option>follow_up</option><option>meeting</option><option>quotation_follow_up</option><option>payment_reminder</option><option>reengagement</option></select></label></div><div><label>Assignee<select name="assigned_to"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label></div><div class="span-2"><label>Note<textarea name="note"></textarea></label></div></div><button class="btn primary">Schedule</button></form></section>
<section class="panel"><h2>Upcoming & overdue</h2><table><thead><tr><th>When</th><th>Type</th><th>Status</th><th>Assigned</th><th></th></tr></thead><tbody>@foreach($followups as $f)<tr><td>{{ $f->scheduled_for?->format('d M Y H:i') }}</td><td>{{ $f->type }}</td><td>{{ $f->status }}</td><td>{{ $f->assignee?->name ?: '—' }}</td><td>@if($f->status!=='completed')<form method="post" action="{{ route('followups.complete',$f) }}">@csrf<button class="btn">Complete</button></form>@endif</td></tr>@endforeach</tbody></table>{{ $followups->links() }}</section></div>
@endsection
@push('scripts')
<script>
const sources={lead:@json($leads),contact:@json($contacts),customer:@json($customers)};
function label(x,type){if(type==='lead')return x.first_name+' '+(x.last_name||'')+' · '+(x.mobile||'');if(type==='contact')return x.first_name+' '+(x.last_name||'')+' · '+(x.mobile||'');return (x.customer_code||'Customer')+' · '+(x.id);}
function loadSubjects(){const type=document.getElementById('type').value,s=document.getElementById('subject');s.innerHTML='';(sources[type]||[]).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=label(x,type);s.appendChild(o)})}
loadSubjects();
</script>
@endpush