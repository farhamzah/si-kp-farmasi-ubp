@extends('layouts.app')
@section('title', 'Tambah Tempat KP - '.config('app.name'))
@section('page_title', 'Tambah Tempat KP')
@section('content')<form method="POST" action="{{ route('management.kp-places.store') }}">@include('management.places._form')</form>@endsection
