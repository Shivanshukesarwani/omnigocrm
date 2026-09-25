@extends('layouts.app')
@section('title','· Lead')
@section('content')
<div class="page-head">
<div><h1>{{ $lead->first_name }} {{ $lead->last_name }}</h1><p class="muted">{{ $lead->company ?: 'Individual lead' }} · {{ $lead->mobile }}</p></div>
<div class="actions">
@if($lead->whatsapp || $lead->mobile)<button class="btn primary" id="waBtnTop">WhatsApp</button>@endif
@if($lead->converted_contact_id)<a class="btn" href="{{ route('contacts.show',$lead->converted_contact_id) }}">Open Contact</a>@else<form method="POST" action="{{ route('leads.convert',$lead) }}">@csrf<button class="btn">Convert to Contact</button></form>@endif
</div></div>

<div class="two-col">
<section class="panel"><h2>Lead details</h2><div class="detail-grid">
<span>Mobile</span><strong>{{ $lead->mobile }}</strong><span>WhatsApp</span><strong>{{ $lead->whatsapp ?: '—' }}</strong><span>Email</span><strong>{{ $lead->email ?: '—' }}</strong><span>Source</span><strong>{{ $lead->source ?: '—' }}</strong><span>Status</span><strong>{{ $lead->status }}</strong><span>Pipeline</span><strong>{{ $lead->pipeline_stage }}</strong><span>Owner</span><strong>{{ $lead->assignee?->name ?: 'Unassigned' }}</strong></div><hr><p><b>Requirement:</b> {{ $lead->requirement ?: '—' }}</p><p><b>Notes:</b> {{ $lead->notes ?: '—' }}</p></section>

<section class="panel"><h2>WhatsApp message</h2><p class="muted">Choose a situation. You can edit the text before opening WhatsApp.</p><select id="templateSituation"><option value="">Select situation</option>@foreach($templates->unique('situation') as $t)<option value="{{ $t->situation }}">{{ ucwords(str_replace('_',' ',$t->situation)) }}</option>@endforeach</select><textarea id="waPreview" style="min-height:180px" placeholder="Message preview"></textarea><button id="waOpen" class="btn primary" disabled>Open WhatsApp</button></section>
</div>

<section class="panel"><h2>Calls</h2><table><thead><tr><th>Date</th><th>Phone</th><th>Direction</th><th>Duration</th><th>Recording</th></tr></thead><tbody>
@forelse($lead->calls as $call)<tr><td>{{ $call->called_at }}</td><td>{{ $call->phone }}</td><td>{{ $call->direction }}</td><td>{{ $call->durationLabel() }}</td><td>@if($call->recording_path && in_array(request()->attributes->get('crmUser')->role,['admin','super_admin'],true))<a href="{{ route('calls.recording',$call) }}" target="_blank">Play</a>@elseif($call->recording_path)<span class="muted">Admin only</span>@else—@endif</td></tr>@empty<tr><td colspan="5">No calls logged.</td></tr>@endforelse
</tbody></table></section>
@endsection
@push('scripts')
<script>
const templates=@json($templates->map(fn($t)=>['situation'=>$t->situation,'body'=>$t->body])->values());
const firstName=@json($lead->first_name);
const lastName=@json($lead->last_name ?? '');
const company=@json($lead->company ?? '');
const requirement=@json($lead->requirement ?? '');
const salesperson=@json(request()->attributes->get('crmUser')->name);
const companyName=@json(config('app.company_name'));
const phone=@json($lead->whatsapp ?: $lead->mobile);
const select=document.getElementById('templateSituation'),preview=document.getElementById('waPreview'),open=document.getElementById('waOpen'),top=document.getElementById('waBtnTop');
function renderBody(body){return body.replaceAll('{first_name}',firstName).replaceAll('{last_name}',lastName).replaceAll('{company}',company).replaceAll('{requirement}',requirement).replaceAll('{salesperson}',salesperson).replaceAll('{company_name}',companyName).replaceAll('{phone}',phone);}
function openWhatsApp(){if(!preview.value)return;const n=phone.replace(/\D/g,'');window.open('https://wa.me/'+n+'?text='+encodeURIComponent(preview.value),'_blank')}
select?.addEventListener('change',()=>{const t=templates.find(x=>x.situation===select.value);preview.value=t?renderBody(t.body):'';open.disabled=!t});
open?.addEventListener('click',openWhatsApp);top?.addEventListener('click',openWhatsApp);
</script>
@endpush