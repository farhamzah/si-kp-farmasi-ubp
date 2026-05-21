@extends('layouts.app')
@section('title', 'Tambah Kuota Tempat KP - '.config('app.name'))
@section('page_title', 'Tambah Kuota Tempat KP')
@section('content')<form method="POST" action="{{ route('management.kp-place-quotas.store') }}">@include('management.quotas._form')</form>@endsection
