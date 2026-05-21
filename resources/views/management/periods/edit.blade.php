@extends('layouts.app')
@section('title', 'Edit Periode KP - '.config('app.name'))
@section('page_title', 'Edit Periode KP')
@section('content')
<form method="POST" action="{{ route('management.kp-periods.update', $period) }}">@include('management.periods._form')</form>
@endsection
