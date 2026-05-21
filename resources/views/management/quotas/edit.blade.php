@extends('layouts.app')
@section('title', 'Edit Kuota Tempat KP - '.config('app.name'))
@section('page_title', 'Edit Kuota Tempat KP')
@section('content')<form method="POST" action="{{ route('management.kp-place-quotas.update', $quota) }}">@include('management.quotas._form')</form>@endsection
