@extends('layouts.app')
@section('title', 'Tambah Periode KP - '.config('app.name'))
@section('page_title', 'Tambah Periode KP')
@section('content')
<form method="POST" action="{{ route('management.kp-periods.store') }}">@include('management.periods._form')</form>
@endsection
