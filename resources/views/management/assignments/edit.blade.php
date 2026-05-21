@extends('layouts.app')
@section('title','Edit Penempatan KP - '.config('app.name'))
@section('page_title','Edit Penempatan KP')
@section('content')
<section class="max-w-3xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">@if($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('management.kp-assignments.update',$assignment) }}" class="space-y-4">@csrf @method('PUT')@include('management.assignments.partials.form-fields',['assignment'=>$assignment])<button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Update Pembimbing</button></form></section>
@endsection
