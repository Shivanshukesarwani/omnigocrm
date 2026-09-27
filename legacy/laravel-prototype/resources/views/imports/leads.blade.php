@extends('layouts.app')
@section('title','· Import Leads')
@section('content')
<div class="page-head"><div><h1>Bulk Lead Import</h1><p class="muted">Upload CSV data into your workspace.</p></div></div>
<section class="panel"><h2>CSV format</h2><p>Required: first_name, mobile. Optional: last_name, company, email, whatsapp, source, status, pipeline_stage, requirement, notes.</p><form method="post" action="{{ route('imports.leads.post') }}" enctype="multipart/form-data">@csrf<label>CSV file<input type="file" name="csv" accept=".csv,text/csv" required></label><button class="btn primary" style="margin-top:12px">Import</button></form></section>
@endsection