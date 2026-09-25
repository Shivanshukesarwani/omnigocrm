<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ config('app.name') }} @yield('title')</title><link rel="stylesheet" href="/css/app.css"></head>
<body>
<div class="shell">
<header class="topbar"><div><strong>{{ config('app.company_name') }}</strong><span class="muted">CRM</span></div><div class="top-actions">@php($u=request()->attributes->get('crmUser'))<span>{{ $u?->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn ghost">Logout</button></form></div></header>
<div class="body-grid">
<aside class="sidebar"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('leads.index') }}">Leads</a><a href="{{ route('contacts.index') }}">Contacts</a><a href="{{ route('customers.index') }}">Customers</a><a href="{{ route('templates.index') }}">WhatsApp Templates</a></aside>
<main class="content">@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif @yield('content')</main>
</div></div>@stack('scripts')
</body></html>
